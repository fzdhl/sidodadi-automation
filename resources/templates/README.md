# Word templates

The supplied templates use these filenames:

- `KETERANGAN DOMISILI.docx`
- `KETERANGAN USAHA.docx`
- `KETERANGAN TIDAK MAMPU.docx`
- `KETERANGAN BIASA.docx`

PHPWord `TemplateProcessor` requires `.docx` templates.

The templates must use the agreed PHPWord placeholders, for example `{{nama}}` and `{{nik}}`. The application fills placeholders and leaves the rest of the original Word layout unchanged.
