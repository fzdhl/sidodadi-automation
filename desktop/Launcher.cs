using System;
using System.IO;
using System.IO.Compression;
using System.Diagnostics;
using System.Reflection;
using System.Windows.Forms;
using System.Threading;
using System.Management;
using System.Drawing;

namespace SidodadiLauncher
{
    static class Program
    {
        private static Mutex mutex;

        [STAThread]
        static void Main()
        {
            // Clean up any orphan Edge profile processes from previous crashed sessions
            KillEdgeProfileProcesses();

            bool createdNew;
            mutex = new Mutex(true, "SidodadiDocGenSingleInstanceMutex", out createdNew);

            if (!createdNew)
            {
                MessageBox.Show("Aplikasi Sidodadi Generator sudah sedang berjalan atau sedang dimuat.", "Informasi", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            SplashForm splash = new SplashForm();
            Application.EnableVisualStyles();
            Thread splashThread = new Thread(() => Application.Run(splash));
            splashThread.SetApartmentState(ApartmentState.STA);
            splashThread.Start();

            string tempDir = Path.Combine(Path.GetTempPath(), "SidodadiPortableApp");

            try
            {
                splash.UpdateStatus("Memeriksa komponen aplikasi...");

                // Step 1: Kill any running PHP background processes first so files aren't locked
                try
                {
                    foreach (var proc in Process.GetProcessesByName("php"))
                    {
                        try { proc.Kill(); } catch { }
                    }
                }
                catch { }

                // Step 2: Fresh Clean Extraction (Ensures complete, uncorrupted files)
                string phpExe = Path.Combine(tempDir, "php", "php.exe");
                string artisanFile = Path.Combine(tempDir, "www", "artisan");

                // Always ensure clean payload extraction
                splash.UpdateStatus("Menyiapkan berkas aplikasi...");
                Assembly assembly = Assembly.GetExecutingAssembly();
                using (Stream stream = assembly.GetManifestResourceStream("payload.zip"))
                {
                    if (stream != null)
                    {
                        if (!Directory.Exists(tempDir))
                        {
                            Directory.CreateDirectory(tempDir);
                        }

                        // Extract directly from assembly stream into temp directory with overwrite
                        using (ZipArchive archive = new ZipArchive(stream, ZipArchiveMode.Read))
                        {
                            int total = archive.Entries.Count;
                            int count = 0;
                            foreach (ZipArchiveEntry entry in archive.Entries)
                            {
                                count++;
                                if (count % 10 == 0 || count == total)
                                {
                                    splash.UpdateStatus(string.Format("Mengekstrak berkas ({0}/{1})...", count, total));
                                }

                                string destinationPath = Path.Combine(tempDir, entry.FullName);

                                if (string.IsNullOrEmpty(entry.Name))
                                {
                                    if (!Directory.Exists(destinationPath))
                                    {
                                        Directory.CreateDirectory(destinationPath);
                                    }
                                    continue;
                                }

                                string parentDir = Path.GetDirectoryName(destinationPath);
                                if (!string.IsNullOrEmpty(parentDir) && !Directory.Exists(parentDir))
                                {
                                    Directory.CreateDirectory(parentDir);
                                }

                                try
                                {
                                    entry.ExtractToFile(destinationPath, true);
                                }
                                catch { }
                            }
                        }
                    }
                    else
                    {
                        splash.CloseForm();
                        MessageBox.Show("Resource payload.zip tidak ditemukan dalam aplikasi.", "Sidodadi Generator Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }
                }

                phpExe = Path.Combine(tempDir, "php", "php.exe");
                string phpIni = Path.Combine(tempDir, "php", "php.ini");
                string wwwPublic = Path.Combine(tempDir, "www", "public");

                if (!File.Exists(phpExe))
                {
                    splash.CloseForm();
                    MessageBox.Show("File runtime PHP (" + phpExe + ") tidak ditemukan.", "Sidodadi Generator Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Step 3: Start PHP Server
                splash.UpdateStatus("Menjalankan server lokal...");
                ProcessStartInfo phpPsi = new ProcessStartInfo();
                phpPsi.FileName = phpExe;
                phpPsi.Arguments = "-c \"" + phpIni + "\" -S 127.0.0.1:8000 -t \"" + wwwPublic + "\"";
                phpPsi.WorkingDirectory = tempDir;
                phpPsi.WindowStyle = ProcessWindowStyle.Hidden;
                phpPsi.CreateNoWindow = true;

                Process phpProcess = Process.Start(phpPsi);
                Thread.Sleep(2000); // Allow 2s for PHP to start listening

                // Step 4: Find Microsoft Edge or Chrome
                splash.UpdateStatus("Membuka jendela aplikasi...");
                string browserPath = "";
                string[] candidates = new string[] {
                    @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
                    @"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
                    @"C:\Program Files\Google\Chrome\Application\chrome.exe",
                    @"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
                };

                foreach (string candidate in candidates)
                {
                    if (File.Exists(candidate))
                    {
                        browserPath = candidate;
                        break;
                    }
                }

                // Step 5: Launch Browser in App Mode with background mode disabled
                ProcessStartInfo browserPsi = new ProcessStartInfo();
                string edgeProfileDir = Path.Combine(Path.GetTempPath(), "SidodadiEdgeProfile");
                if (!string.IsNullOrEmpty(browserPath))
                {
                    browserPsi.FileName = browserPath;
                    browserPsi.Arguments = "--app=http://127.0.0.1:8000 --user-data-dir=\"" + edgeProfileDir + "\" --disable-background-mode --no-first-run --no-default-browser-check";
                }
                else
                {
                    browserPsi.FileName = "http://127.0.0.1:8000";
                }

                Process.Start(browserPsi);

                // Wait 1.5s then close splash screen
                Thread.Sleep(1500);
                splash.CloseForm();

                // Keep PHP running while the Sidodadi Edge App Window is open
                int checkCount = 0;
                while (checkCount < 3 || IsEdgeAppRunning())
                {
                    Thread.Sleep(1000);
                    checkCount++;
                }

                // Clean up PHP process on exit
                try
                {
                    if (phpProcess != null && !phpProcess.HasExited)
                    {
                        phpProcess.Kill();
                    }
                }
                catch { }

                // Clean up any remaining Edge profile processes
                KillEdgeProfileProcesses();
            }
            catch (Exception ex)
            {
                splash.CloseForm();
                MessageBox.Show("Gagal menjalankan aplikasi: " + ex.Message, "Sidodadi Generator Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            finally
            {
                try { mutex.ReleaseMutex(); } catch { }
            }
        }

        private static bool IsEdgeAppRunning()
        {
            try
            {
                using (var searcher = new ManagementObjectSearcher("SELECT CommandLine FROM Win32_Process WHERE Name = 'msedge.exe' OR Name = 'chrome.exe'"))
                {
                    foreach (var obj in searcher.Get())
                    {
                        string cmd = obj["CommandLine"] != null ? obj["CommandLine"].ToString() : "";
                        if (cmd.IndexOf("127.0.0.1:8000", StringComparison.OrdinalIgnoreCase) >= 0 ||
                            (cmd.IndexOf("SidodadiEdgeProfile", StringComparison.OrdinalIgnoreCase) >= 0 && cmd.IndexOf("--app=", StringComparison.OrdinalIgnoreCase) >= 0))
                        {
                            return true;
                        }
                    }
                }
            }
            catch { }
            return false;
        }

        private static void KillEdgeProfileProcesses()
        {
            try
            {
                using (var searcher = new ManagementObjectSearcher("SELECT ProcessId, CommandLine FROM Win32_Process WHERE Name = 'msedge.exe' OR Name = 'chrome.exe'"))
                {
                    foreach (var obj in searcher.Get())
                    {
                        string cmd = obj["CommandLine"] != null ? obj["CommandLine"].ToString() : "";
                        if (cmd.IndexOf("SidodadiEdgeProfile", StringComparison.OrdinalIgnoreCase) >= 0)
                        {
                            try
                            {
                                int pid = Convert.ToInt32(obj["ProcessId"]);
                                Process proc = Process.GetProcessById(pid);
                                proc.Kill();
                            }
                            catch { }
                        }
                    }
                }
            }
            catch { }
        }
    }

    public class SplashForm : Form
    {
        private Label lblStatus;
        private ProgressBar progressBar;

        public SplashForm()
        {
            this.Text = "Sidodadi Document Generator";
            this.Size = new Size(420, 175);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.ControlBox = false;
            this.TopMost = true;
            this.BackColor = Color.FromArgb(248, 250, 252);

            Label lblTitle = new Label();
            lblTitle.Text = "📄 Sidodadi Document Generator";
            lblTitle.Font = new Font("Segoe UI", 12, FontStyle.Bold);
            lblTitle.ForeColor = Color.FromArgb(30, 41, 59);
            lblTitle.Location = new Point(20, 18);
            lblTitle.AutoSize = true;

            lblStatus = new Label();
            lblStatus.Text = "Memuat aplikasi, mohon tunggu...";
            lblStatus.Font = new Font("Segoe UI", 9, FontStyle.Regular);
            lblStatus.ForeColor = Color.FromArgb(71, 85, 105);
            lblStatus.Location = new Point(22, 52);
            lblStatus.Size = new Size(360, 25);

            progressBar = new ProgressBar();
            progressBar.Style = ProgressBarStyle.Marquee;
            progressBar.MarqueeAnimationSpeed = 30;
            progressBar.Location = new Point(22, 82);
            progressBar.Size = new Size(360, 20);

            this.Controls.Add(lblTitle);
            this.Controls.Add(lblStatus);
            this.Controls.Add(progressBar);
        }

        public void UpdateStatus(string message)
        {
            if (this.InvokeRequired)
            {
                this.BeginInvoke(new Action(() => UpdateStatus(message)));
            }
            else
            {
                lblStatus.Text = message;
            }
        }

        public void CloseForm()
        {
            if (this.InvokeRequired)
            {
                this.BeginInvoke(new Action(() => CloseForm()));
            }
            else
            {
                this.Close();
            }
        }
    }
}
