## Plan: NIK Lookup and Integrated Resident Database

TL;DR: Build a GUI-backed resident lookup system with Excel import/export as a companion tool. Start by stabilizing current generator/form behavior, then add an internal data layer, then connect lookup UI and import/export flows.

**Phase 1: Stabilize current foundation**
1. Confirm `config/documents.php` field definitions are consistent with templates.
2. Ensure date formatting works for both `tanggal_surat` and `tanggal_lahir` before TTL construction.
3. Confirm form rendering supports `placeholder` attributes for `input` and `textarea`.
4. Keep the analyzer command/report and generator separate from runtime bold detection.
5. Add validation/testing coverage for:
   - date formatting of date fields,
   - placeholder rendering in the form,
   - config-driven uppercase behavior.

**Phase 2: Build internal resident lookup layer**
1. Choose an internal storage mechanism for runtime lookup:
   - SQLite is a good default for this project, or Laravel DB if available.
2. Define resident data model / schema with fields used by documents:
   - `nik`, `nama`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `agama`, `status_perkawinan`, `kewarganegaraan`, `pekerjaan`, `alamat`, `jenis_usaha`, `nama_usaha`, `lama_usaha`, `alamat_usaha`, `nama_anak`, `nik_anak`, `nama_sekolah`, etc.
3. Create a `ResidentLookup` service/repository:
   - `findByNik(string $nik): ?Resident`
   - `saveResident(array $data)`
4. Add a simple migration and model if using database.

**Phase 3: Add import/export support**
1. Add an Excel import feature:
   - use a package like `maatwebsite/excel` or build simple CSV/Excel reader.
   - map Excel columns into internal resident fields.
   - import data into internal storage.
2. Add Excel export feature:
   - allow exporting current resident dataset to `.xlsx`.
   - include newly added/updated records.
3. Provide UI pages for import/export management.

**Phase 4: Build NIK lookup and data enrichment UI**
1. Add lookup UI to the document form:
   - a `NIK` input plus `Lookup` button.
   - AJAX call to lookup endpoint.
2. If found, autofill relevant form fields.
3. If not found:
   - show a friendly message,
   - allow manual fill,
   - offer a “save as new resident” option.
4. Add an admin/resident table view:
   - searchable/filterable by NIK, name, status.
   - inline edit or edit form.
   - row-level actions: edit, export, mark as verified.
5. Keep manual edit fallback strong:
   - users can always override autofilled values.
   - validation still applies.

**Phase 5: polish and documentation**
1. Add feature tests for lookup, import, export, and fallback flows.
2. Add UI guidance so the village staff understands:
   - when lookup succeeds,
   - when data is missing,
   - how to save new resident data.
3. Document the recommended workflow:
   - import Excel,
   - lookup NIK,
   - if missing, complete and save,
   - export backup.
4. Keep Excel support as companion rather than runtime storage.

**Decisions / scope**
- GUI storage should be the application’s primary runtime source.
- Excel should be import/export only, not the live database.
- The first implementation can use a small local SQLite DB or Laravel model.
- Later, if needed, add a dedicated import data management page.

**Next recommended immediate step**
1. implement resident schema/service and lookup endpoint,
2. wire lookup into the document form,
3. keep Excel import/export as the next step after lookup works.
