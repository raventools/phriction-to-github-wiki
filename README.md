# Export Utility for the Phabricator Wiki

We decided to stop using `Phriction`, the wiki tool provided as part of Phabricator. Instead, we wanted to export it use the GitHub wiki platform.

## Quick run down

* Command-line interface
* Exports each Phriction document into a separate Markdown file
* Fixes links to existing documents

## Known issues

* Exports to subfolders for easy organization (but GitHub will ignore that entirely)
* Does the bare minimum in terms of cleaning up the formatting (Remarkup differs from Markdown by a lot)

## Using it

1. I'd suggest exporting the `phabricator_phriction` database to your local machine to work with this rather than connecting to the production database
2. Copy the `config.php-dist` to `config.php` in the root directory and update with your database details
3. Run `make` from the command line.
4. Run `make clean` to empty the directory
5. Run `make run` to just run as-is

Note: You can shortcut those steps if you don't want to use the `Makefile` by just typing `php run.php` from the project root directory. Clean up the folder as needed.