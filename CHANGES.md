#### [Version 0.5.2 Beta](https://github.com/ForallFramework/core.package/tree/0.5.2-beta)
_19-Aug-2013_

* Removed out-of-place HTTP function.

#### [Version 0.5.1 Beta](https://github.com/ForallFramework/core.package/tree/0.5.1-beta)
_17-Aug-2013_

* Added the defaultTimezone and overrideServerTimezone settings to the core package for
  servers that do not have this configured.
* Added the useSingleLogFile setting for servers that have their own log file handlers.
* Added a JSON minifyer to allow for comments in JSON files.
* Added comments to the settings.json file.
* Removed trailing white-spaces throughout.

#### [Version 0.5.0 Beta](https://github.com/ForallFramework/core.package/tree/0.5.0-beta)
_14-Aug-2013_

* Separated core utility functions into a Utils class.
* Implemented Monolog  logging.

#### [Version 0.4.0 Beta](https://github.com/ForallFramework/core.package/tree/0.4.0-beta)
_15-June-2013_

* Changed the way the core package uses PackageDescriptor's.
  - Instead of PackageDescriptors being made for each package straight away, the core now
    offers an interface for requesting a specific PackageDescriptor to be created.
  - Instead of requesting an array of packageDescriptors to iterate packages, the core now
    offers an interface for iterating over all package names.
* Changed the role of the PackageDescriptor.
  - Instead of requesting set variables from the PackageDescriptor through static getters,
    the PackageDescriptor now offers a dynamic getter for each JSON-file in the root of
    the package.
* Added `Core::getLoader()` to access the composer ClassLoader instance.

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
