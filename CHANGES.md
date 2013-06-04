#### [Version 0.3.0 Beta](https://github.com/ForallFramework/core.package/tree/0.3.0-beta)
_4-June-2013_

* Migrated to composer.
  - Moved all source files to `vendor/packagename` subdirectories.
  - Added `composer.json` and removed old json files.
  - Removed custom loading of class files.
  - Added the inclusion of the composer autoloader.

#### [Version 0.2.1 Beta](https://github.com/ForallFramework/core.package/tree/0.2.1-beta)
_30-May-2013_

* Removed unused data from `forall.json`.
* Removed version tags from files.
* Switched to the new @Tuxion versioning standard.

#### [Version 0.2 Beta 1](https://github.com/ForallFramework/core.package/tree/v0.2-beta1)
_27-May-2013_

* Renamed `package.json` to `forall.json`.
* Added the `Core::onMainFilesIncluded` method.
* Added the `Core::normalizePackageName` method.
* Bug fixes:
  - Missing vendor from name spaces.
  - When packages are gathered twice, already found packages are not ignored.
  - Some methods in FileIncluder don't return self, as described in their doc-blocks.

#### [Version 0.1 Beta 1](https://github.com/ForallFramework/core.package/tree/v0.1-beta1)
_10-May-2013_

* First significant version.
