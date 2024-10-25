# Package Versions

**`composer/package-versions-deprecated` is a fully-compatible fork of [`ocramius/package-versions`](https://github.com/Ocramius/PackageVersions)** which provides compatibility with Composer 1 and 2 on PHP 7+. It replaces ocramius/package-versions so if you have a dependency requiring it and you want to use Composer v2 but can not upgrade to PHP 7.4 just yet, you can require this package instead.

If you have a **direct** dependency on `ocramius/package-versions`, we recommend that once you migrated to Composer 2.x you also migrate to use the [`Composer\InstalledVersions`](https://getcomposer.org/doc/07-runtime.md#installed-versions) class which offers the functionality present here out of the box. You can then remove the require on this package.

This package is EOL / deprecated and you should aim to migrate away from it as soon as possible!
