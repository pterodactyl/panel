Vagrant.configure("2") do |config|
    config.vm.box = "bento/ubuntu-16.04"

    config.vm.synced_folder "./", "/var/www/html/pterodactyl",
        owner: "www-data", group: "www-data"

    #config.vm.provision :file, source: ".dev/vagrant/pterdactyl.conf", destination: "/etc/nginx/sites-available/pterodactyl.conf"
    #config.vm.provision :file, source: ".dev/vagrant/pteroq.service", destination: "/etc/systemd/system/pteroq.service"
    #config.vm.provision :file, source: ".dev/vagrant/mailhog.service", destination: "/etc/systemd/system/mailhog.service"
    #config.vm.provision :file, source: ".dev/vagrant/.env", destination: "/var/www/html/pterodactyl/.env"
    config.vm.provision :shell, path: ".dev/vagrant/provision.sh"

    config.vm.network :private_network, ip: "192.168.50.2"
    config.vm.network :forwarded_port, guest: 80, host: 50080
    config.vm.network :forwarded_port, guest: 8025, host: 58025
    config.vm.network :forwarded_port, guest: 3306, host: 53306

    # Config for the vagrant-dns plugin (https://github.com/BerlinVagrant/vagrant-dns)
    config.dns.tld = "test"
    config.dns.patterns = [/^pterodactyl.test$/]
end
