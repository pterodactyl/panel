# Contributing
We're glad you want to help us out and make this panel the best that it can be! We have a few simple things to follow
when making changes to files and adding new features.

### Development Environment
Please check the [`pterodactyl/development`](https://github.com/pterodactyl/development) repository for a Vagrant &
Docker setup that should run on most macOS and Linux distributions. In the event that your platform is not supported
you're welcome to open a PR, or just take a look at our setup scripts to see what you'll need to successfully develop
with Pterodactyl.

#### Building Assets
Please see [`BUILDING.md`](https://github.com/pterodactyl/panel/blob/develop/BUILDING.md) for details on how to actually
build and run the development server.

### Project Branches
This section mainly applies to those with read/write access to our repositories, but can be helpful for others.

The `develop` branch should always be in a runnable state, and not contain any major breaking features. For the most
part, this means you will need to create `feature/` branches in order to add new functionality or change how things
work. When making a feature branch, if it is referencing something in the issue tracker, please title the branch
`feature/PTDL-###` where `###` is the issue number.

All new code should contain tests to ensure their functionality is not unintentionally changed down the road. This
is especially important for any API actions or authentication based controls.

### The CHANGELOG
You should not make any changes to the `CHANGELOG.md` file during your code updates. This is updated by the maintainers
at the time of deployment to include the relevant changes that were made.

### Code Guidelines
We are a `PSR-4` and `PSR-0` compliant project, so please follow those guidelines at a minimum. In addition we run
`php-cs-fixer` on all PRs and releases to enforce a consistent code style. The following command executed on your machine
should show any areas where the code style does not line up correctly.

```
vendor/bin/php-cs-fixer fix --dry-run --diff --diff-format=udiff --config .php_cs.dist
```

### Responsible Disclosure
This is a fairly in-depth project and makes use of a lot of parts. We strive to keep everything as secure as possible
and welcome you to take a look at the code provided in this project yourself. We do ask that you be considerate of
others who are using the software and not publicly disclose security issues without contacting us first by email.

We'll make a deal with you: if you contact us by email and we fail to respond to you within a week you are welcome to
publicly disclose whatever issue you have found. We understand how frustrating it is when you find something big and
no one will respond to you. This holds us to a standard of providing prompt attention to any issues that arise and
keeping this community safe.

If you've found what you believe is a security issue please email `dane åt pterodactyl døt io`.

### Contact Us
You can find us in a couple places online. First and foremost, we're active right here on Github. If you encounter a
bug or other problems, open an issue on here for us to take a look at it. We also accept feature requests here as well.

You can also find us on [Discord](https://discord.gg/pterodactyl).
