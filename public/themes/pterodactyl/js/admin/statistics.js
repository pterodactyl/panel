var freeDisk = Pterodactyl.totalNodeDisk - Pterodactyl.totalServerDisk;
let diskChart = new Chart($('#disk_chart'), {
    type: 'pie',
    data: {
        labels: ['Free Disk', 'Used Disk'],
        datasets: [
            {
                label: 'Disk (in MB)',
                backgroundColor: ['#51B060', '#ff0000'],
                data: [freeDisk, Pterodactyl.totalServerDisk]
            }
        ]
    }
});

var freeRam = Pterodactyl.totalNodeRam - Pterodactyl.totalServerRam;
let ramChart = new Chart($('#ram_chart'), {
    type: 'pie',
    data: {
        labels: ['Free RAM', 'Used RAM'],
        datasets: [
            {
                label: 'Memory (in MB)',
                backgroundColor: ['#51B060', '#ff0000'],
                data: [freeRam, Pterodactyl.totalServerRam]
            }
        ]
    }
});

var activeServers = Pterodactyl.servers.length - Pterodactyl.suspendedServers;
let serversChart = new Chart($('#servers_chart'), {
    type: 'pie',
    data: {
        labels: ['Active', 'Suspended'],
        datasets: [
            {
                label: 'Servers',
                backgroundColor: ['#51B060', '#E08E0B'],
                data: [activeServers, Pterodactyl.suspendedServers]
            }
        ]
    }
});

let statusChart = new Chart($('#status_chart'), {
    type: 'pie',
    data: {
        labels: ['Online', 'Offline', 'Installing', 'Error'],
        datasets: [
            {
                label: '',
                backgroundColor: ['#51B060', '#b7b7b7', '#E08E0B', '#ff0000'],
                data: [0,0,0,0]
            }
        ]
    }
});

var servers = Pterodactyl.servers;
var nodes = Pterodactyl.nodes;

for (let i = 0; i < servers.length; i++) {
    setTimeout(getStatus, 200 * i, servers[i]);
}

function getStatus(server) {
    var uuid = server.uuid;
    var node = getNodeByID(server.node_id);

    $.ajax({
        type: 'GET',
        url: node.scheme + '://' + node.fqdn + ':'+node.daemonListen+'/v1/server',
        timeout: 5000,
        headers: {
            'X-Access-Server': uuid,
            'X-Access-Token': Pterodactyl.tokens[node.id],
        }
    }).done(function (data) {

        if (typeof data.status === 'undefined') {
            // Error
            statusChart.data.datasets[0].data[3]++;
            return;
        }

        switch (data.status) {
            case 0:
            case 3:
            case 30:
                // Offline
                statusChart.data.datasets[0].data[1]++;
                break;
            case 1:
            case 2:
                // Online
                statusChart.data.datasets[0].data[0]++;
                break;
            case 20:
                // Installing
                statusChart.data.datasets[0].data[2]++;
                break;
        }
        statusChart.update();
    }).fail(function (jqXHR) {
        // Error
        statusChart.data.datasets[0].data[3]++;
        statusChart.update();
    });
}

function getNodeByID(id) {
    for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].id === id) {
            return nodes[i];
        }
    }
}