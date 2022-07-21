// Jexactyl Software. (https://jexactyl.com)
// Green: #189a1c
// Gray: hsl(211, 22%, 21%)

const suspended = Pterodactyl.suspendedServers;
const freeRam = Pterodactyl.totalNodeRam - Pterodactyl.totalServerRam;
const active = Pterodactyl.servers.length - Pterodactyl.suspendedServers;
const freeDisk = Pterodactyl.totalNodeDisk - Pterodactyl.totalServerDisk;

const diskChart = new Chart($("#disk_chart"), {
    type: "pie",
    data: {
        labels: ["Free Disk", "Used Disk"],
        datasets: [{
            backgroundColor: ["#189a1c", "hsl(211, 22%, 21%)"],
            data: [freeDisk, Pterodactyl.totalServerDisk]
        }]
    }
});

const ramChart = new Chart($("#ram_chart"), {
    type: "pie",
    data: {
        labels: ["Free RAM", "Used RAM"],
        datasets: [{
            backgroundColor: ["#189a1c", "hsl(211, 22%, 21%)"],
            data: [freeRam, Pterodactyl.totalServerRam]
        }]
    }
});

const serversChart = new Chart($("#servers_chart"), {
    type: "pie",
    data: {
        labels: ["Active Servers", "Suspended Servers"],
        datasets: [{
            backgroundColor: ["#189a1c", "hsl(211, 22%, 21%)"],
            data: [active, suspended]
        }]
    }
});

const statusChart = new Chart($("#status_chart"), {
    type: "pie",
    data: {
        labels: ["Running Servers", "Offline Servers"],
        datasets: [{
            backgroundColor: ["#189a1c", "hsl(211, 22%, 21%)"],
            data: [0, 0]
        }]
    }
});

const servers = Pterodactyl.servers;
for (let t = 0; t < servers.length; t++) getStatus(servers[t]);

function getStatus(t) {
    var a = Pterodactyl.serverstatus[t.uuid].attributes.current_state;
    "running" == a ? statusChart.data.datasets[0].data[0]++ : statusChart.data.datasets[0].data[1]++, statusChart.update();
}
