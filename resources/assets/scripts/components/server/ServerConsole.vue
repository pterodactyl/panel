<template>
    <div>
        <div class="text-xs font-mono">
            <div class="rounded-t p-2 bg-black text-grey-lightest" style="height: 400px;">openjdk version "1.8.0_151"<br/>
                OpenJDK Runtime Environment (IcedTea 3.6.0) (Alpine 8.151.12-r0)<br/>
                OpenJDK 64-Bit Server VM (build 25.151-b12, mixed mode)<br/>
                :/home/container$ java -Xms128M -Xmx512M -jar server.jar<br/>
                [14:07:28] [Server thread/INFO]: Starting minecraft server version 1.12.2<br/>
                [14:07:28] [Server thread/WARN]: To start the server with more ram, launch it as "java -Xmx1024M
                -Xms1024M -jar minecraft_server.jar"<br/>
                [14:07:28] [Server thread/INFO]: Loading properties<br/>
                [14:07:29] [Server thread/INFO]: Default game type: SURVIVAL<br/>
                [14:07:29] [Server thread/INFO]: Generating keypair<br/>
                [14:07:29] [Server thread/INFO]: Starting Minecraft server on 0.0.0.0:25565<br/>
                [14:07:29] [Server thread/INFO]: Using epoll channel type<br/>
                [14:07:29] [Server thread/INFO]: Preparing level "world"<br/>
                [14:07:30] [Server thread/INFO]: Loaded 488 advancements<br/>
                [14:07:30] [Server thread/INFO]: Preparing start region for level 0<br/>
                [14:07:31] [Server thread/INFO]: Preparing spawn area: 4%<br/>
                [14:07:32] [Server thread/INFO]: Preparing spawn area: 33%<br/>
                [14:07:33] [Server thread/INFO]: Preparing spawn area: 99%<br/>
                [14:07:33] [Server thread/INFO]: Done (3.856s)! For help, type "help" or "?"<br/>
                [14:07:33] [Server thread/INFO]: Starting GS4 status listener<br/>
                [14:07:33] [Query Listener #1/INFO]: Query running on 0.0.0.0:25565<br/></div>
            <div class="rounded-b p-2 bg-grey-darkest text-white">$</div>
        </div>
        <div class="flex mt-8">
            <div class="flex-1 p-4 mr-4 border border-grey-lighter rounded"><h3 class="mb-4 text-center">CPU Usage in
                %</h3>
                <canvas class="w-1/2" id="chartCPU"></canvas>
            </div>
            <div class="flex-1 p-4 ml-4 border border-grey-lighter rounded"><h3 class="mb-4 text-center">Memory Usage in
                GB</h3>
                <canvas class="w-1/2" id="chartMemory"></canvas>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ServerConsole"
    }
</script>
