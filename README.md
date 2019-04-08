# Code signing plugin for [signature-verified-autoloader](https://github.com/curator-wik/composer-signature-verified-autoloader)

Include this package as a dev dependency in order to create hash and/or
signature files to your project to be trusted by the [signature-verified-autoloader](https://github.com/curator-wik/composer-signature-verified-autoloader).

It locates code that is includeable through the autoload rules in your `composer.json`
and adds hash or signature files for them. ***PSR-0 is yet to be implemented*** and will throw
an exception instead for now if your project uses it.

## Usage: Developers
Before tagging a new release, simply run
```bash
composer dump-hashes
```
A `hashes.sha256` file will be created in each directory with at least one file that
can be autoloaded. Include these files in your release. You may also want to obtain
corresponding signature files from some signing authority and include those as well.

## Usage: Signing Authorities
Anyone *can* sign code with a public/private keypair. However you may wish to
have your code signed by an entity who is already trusted by others.

The files that are signed are the `hashes.sha256` files, not the full code.
Multiple signing authorities can simultaneously offer their signatures so that
your package may be verifiable by people who have trusted different signing
authorities.

When acting as a signing authority, all hash files output by `dump-hashes` must
already exist. If they are absent or incorrect, the sigining routine will fail.

To sign the files, run
```bash
composer sign-hashes --private-key /path/to/private_key --signing-authority poc
```
passing
  * `--private-key`: The path to your private key
  * `--signing-authority`: A unique, well-known code you use to identify your
     signing authority. The signature files will contain this code at the end
     of their filenames.
     
On success, `hashes.sha256.sig.[signing authority code]` files will be computed
and placed alongside each `hashes.sha256` file, and a message indicating the
number of hash files signed is output.