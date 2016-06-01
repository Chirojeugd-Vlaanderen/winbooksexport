# be.chiro.civi.winbooksexport

With this extension you can export financial batches to CSV-documents,
that can be imported into [Winbooks](https://www.winbooks.be) using the
crappy [WbLink](http://www.igsoft.eu/WikiFrWinBooks/index.php) tool.

We use it with CiviCRM 4.7. It probably works with 4.6.x, but you will have
to to try (and change the CiviCRM version number in [info.xml](info.xml)).

**Disclaimer:** This extension is crappy as well. :-) It is not production ready. It is probably broken, but it works for us at the moment. More or less. ;-)
You'll probably have to adapt it for your own organisation, or even better:
make it configurable. (I will happily accept pull requests.)

## Configuration

This extension is configured using CiviCRM settings. But there is no form
to configure those settings. So we configure them using the API, or in the
`civicrm.settings.php` file.

### Invoice numbers

Our extension creates invoices for membership dues, and for event fees. The membership invoices get other numbers than the event invoices. The last invoice numbers created by the extensions, are stored in the variables `chirocontribution_max_aansluitingsfactuur_nr` (for membership dues) and `chirocontribution_max_cursusfactuur_nr` (for event fees). Since we don't have a way (yet) to configure those using the gui, we just do it with drush. E.g.

    drush cvapi Setting.create chirocontribution_max_cursusfactuur_nr=1671762

### FTPS settings

The extension does not let you download the exported file, it uploads it to our server using FTPS. (Not SFTP, but FTPS, because it is a Windows server. Argh. ;))

We define the settings for the connection in our `civicrm.settings.php` file:

    $conf['chiroftp_url'] = 'your.ftp.server.com';
    $conf['chiroftp_user'] = 'username';
    $conf['chiroftp_pass'] = 'password';
    $conf['chiroftp_folder'] = 'folder/to/put/the/files/in';

Chances are low that you also want to upload your files using FTPS. So you
will probably have to adapt this in the code, and it would be very cool if
you could make this configurable :-)

## How does it work

### The export format

The meat of the work is done by [Winbooks.php](CRM/Financial/BAO/ExportFormat/Winbooks.php).
I inherited some functions, but the inherited functions sometimes expect or return arrays where they do not in the base class (`ExportFormat`). Technically, this is an error, I can do this because I know how ExportFormat is implemented, but it breaks encapsulation. But as said before, this is a crappy extension :-)

Let's look at the most important functions, starting with the ones you will have to adapt:

The functions `generateCSFQuery`, `generateANTQuery` and `generateACTQuery` create DAOs that returns the fields needed for the files `csf.txt`, `ant.txt` and `act.txt`; this are the files WbLink expects.

The CSF file contains information about the customers.
The ACT file contains information about the invoices, and the ANT file has something to do with analytic things. I have little idea what it's all about. :-P

In the queries for ACT and ANT, I join financial account to financial type on the name field, which is probably an error. I still have to find out what's wrong, but it seems to work anyway.

For the generation of the ACT and ANT files, we depend on custom fields (more than we should...), so you will have to change this.

`prepareWinbooksExport($batchId)` is a hack that generate invoice ID's, and stores those in the trxn_id-field of the contributions. It updates the values of `chirocontribution_max_cursusfactuur_nr` and `chirocontribution_max_aansluitingsfactuur_nr`. This is rather error prone.

`putFile($exports)` normally creates a file, writes the string `$exports` to it, and returns the file name. We changed the functionality: it now expects an array of 3 strings, containing the contents for `act.txt`, `ant.txt` and `csf.txt`.

`generateExportQuery($batchId)` normally creates one DAO that retrieves the information needed for the export. We changed it, so that it returns 3 DAO's. We can do this because the output of this function will only be passed to `makeExport($export)`, which we adapted as well, so that
it accepts an array of 3 DAO's.

The rest of the file should speak for itself.

### Hooks

In [winbooksexport.php](winbooksexport.php) we implement a couple of hooks:

`hook_civicrm_preProcess` tries to makes sure that the Winbooks export is selected by default. I think this works, but I think we better use `$form->setDefaults` in `hook_civiccrm_buildForm`.

In `hook_civicrm_buildForm` I replace the radio buttons for `export_format` with a new set of 1 radio button: Winbooks. (I tried to add an option to the existing set, but it did not work.)

### Templates

The folder [templates/CRM/Financial/ExportFormat](templates/CRM/Financial/ExportFormat) contains templates for the 3 text files. I had to put everything on one line, otherwise WbLink would not accept it.

I also had to add [templates/CRM/Financial/Form/Search.tpl](templates/CRM/Financial/Form/Search.tpl) because otherwise the javascript thingy did not work in the list with open batches.

## Troubleshooting

If you use the FTPS upload, and it fails, check whether the php curl command
trusts the certificate you're using.
