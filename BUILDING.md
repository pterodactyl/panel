# Local Development
Pterodactyl is now powered by Vuejs and Tailwindcss and uses webpack at its core to generate compiled assets. Release
versions of Pterodactyl will include pre-compiled, minified, and hashed assets ready-to-go.

However, if you are interested in running custom themes or making modifications to the Vue files you'll need a build
system in place to generate these compiled assets. To get your environment setup, you'll first need to install at least Nodejs
`8`, and it is _highly_ recommended that you also install [Yarn](https://yarnpkg.com) to manage your `node_modules`.

### Install Dependencies
```bash
yarn install
```

The command above will download all of the dependencies necessary to get Pterodactyl assets building. After that, its as
simple as running the command below to generate assets while you're developing.

```bash
# build the compiled assets for development
yarn run build

# build the assets automatically when files are modified
yarn run watch
```


### Hot Module Reloading
For more advanced users, we also support 'Hot Module Reloading', allowing you to quickly see changes you're making
to the Vue template files without having to reload the page you're on. To Get started with this, you just need
to run the command below.

```bash
PUBLIC_PATH=http://192.168.1.1:8080 yarn run serve --host 192.168.1.1
```

There are two _very important_ parts of this command to take note of and change for your specific environment. The first
is the `--host` flag, which is required and should point to the machine where the `webpack-serve` server will be running.
The second is the `PUBLIC_PATH` environment variable which is the URL pointing to the HMR server and is appended to all of
the asset URLs used in Pterodactyl.

#### Vagrant
If you want to use HMR with our Vagrant image, you can use `yarn run v:serve` as a shortcut for the correct parameters.
In order to have proper file change detection you can use the [`vagrant-notify-forwarder`](https://github.com/mhallin/vagrant-notify-forwarder) to notify file events from the host to the VM.
```sh
vagrant plugin install vagrant-notify-forwarder
vagrant reload
```

### Building for Production
Once you have your files squared away and ready for the live server, you'll be needing to generate compiled, minified, and
hashed assets to push live. To do so, run the command below:

```bash
yarn run build:production
```

This will generate a production ready `bundle.js` and `bundle.css` as well as a `manifest.json` and store them in
the `/public/assets` directory where they can then be access by clients, and read by the Panel.
