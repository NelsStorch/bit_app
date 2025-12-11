<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netzwerk-Diagrammersteller mit erweiterten Funktionen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; overflow: hidden; }
        .toolbar {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping for more items */
        }
        .toolbar-item {
            cursor: pointer;
            transition: background-color 0.3s;
            min-width: 80px; /* Ensure items have a minimum width */
        }
        .toolbar-item:hover {
            background-color: #e0e0e0;
        }
        .toolbar-item.selected {
            background-color: #bfdbfe;
            border-color: #3b82f6;
        }
        #networkCanvas {
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            display: block;
        }
        .button-std {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 500; /* medium */
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
            border: 1px solid transparent;
        }
        .button-primary {
            background-color: #3b82f6; /* bg-blue-500 */
            color: white;
        }
        .button-primary:hover {
            background-color: #2563eb; /* hover:bg-blue-600 */
        }
        .button-secondary {
            background-color: #6b7280; /* bg-gray-500 */
            color: white;
        }
        .button-secondary:hover {
            background-color: #4b5563; /* hover:bg-gray-600 */
        }
        .button-success {
            background-color: #10b981; /* bg-green-500 */
            color: white;
        }
        .button-success:hover {
            background-color: #059669; /* hover:bg-green-600 */
        }
        .button-danger {
            background-color: #ef4444; /* bg-red-500 */
            color: white;
        }
        .button-danger:hover {
            background-color: #dc2626; /* hover:bg-red-600 */
        }
        .button-warning {
            background-color: #f59e0b; /* bg-amber-500 */
            color: white;
        }
        .button-warning:hover {
            background-color: #d97706; /* bg-amber-600 */
        }
        .device-icon {
            font-size: 24px;
            text-align: center;
            line-height: 1;
        }
        .tooltip {
            position: absolute;
            background-color: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 1000; /* Ensure tooltip is on top */
        }
        .message-overlay {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0,0,0,0.75);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            z-index: 101;
            font-size: 14px;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 flex flex-col h-screen">

    <div class="bg-white shadow-md p-2 flex items-center space-x-2 print:hidden toolbar">
        <div id="addPc" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="PC hinzufügen">
            <div class="device-icon">💻</div>
            <span class="text-xs mt-1">PC</span>
        </div>
        <div id="addLaptop" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Laptop hinzufügen">
            <div class="device-icon">🖥️</div> <span class="text-xs mt-1">Laptop</span>
        </div>
        <div id="addRouter" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Router hinzufügen">
            <div class="device-icon">🌐</div>
            <span class="text-xs mt-1">Router</span>
        </div>
        <div id="addSwitch" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Switch hinzufügen">
            <div class="device-icon">↔️</div>
            <span class="text-xs mt-1">Switch</span>
        </div>
        <div id="addServer" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Server hinzufügen">
            <div class="device-icon">🗄️</div>
            <span class="text-xs mt-1">Server</span>
        </div>
        <div id="addInternet" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Internet hinzufügen">
            <div class="device-icon">☁️</div>
            <span class="text-xs mt-1">Internet</span>
        </div>
        <div class="flex-grow"></div>
        <button id="connectMode" class="button-std button-secondary">Verbindungsmodus</button>
        <button id="packetSendMode" class="button-std button-secondary">Paket senden</button>
        <button id="deleteMode" class="button-std button-warning">Löschen</button>
        <button id="clearCanvas" class="button-std button-danger">Arbeitsfläche leeren</button>
    </div>

    <div class="flex-grow p-2 relative" id="canvasContainer">
        <canvas id="networkCanvas"></canvas>
        <div id="messageOverlay" class="message-overlay"></div>
    </div>

    <div id="tooltip" class="tooltip"></div>

    <script>
        const canvas = document.getElementById('networkCanvas');
        const ctx = canvas.getContext('2d');
        const canvasContainer = document.getElementById('canvasContainer');
        const messageOverlay = document.getElementById('messageOverlay');

        let devices = [];
        let connections = [];
        let packets = [];
        let nextDeviceId = 0;
        let nextPacketId = 0;
        let selectedDeviceType = null;
        let isConnecting = false;
        let firstDeviceForConnection = null;
        let isSendingPacketMode = false;
        let packetSourceDevice = null;
        let isDeletingMode = false; // New mode for deleting
        let draggingDevice = null;
        let dragOffsetX, dragOffsetY;
        let animationFrameId = null;

        // Properties for each device type
        const deviceProperties = {
            pc: { icon: '💻', baseWidth: 50, baseHeight: 50, color: '#60a5fa', label: 'PC', isForwarder: false, maxPorts: 1 },
            laptop: { icon: '💻', baseWidth: 50, baseHeight: 45, color: '#a78bfa', label: 'Laptop', isForwarder: false, maxPorts: 1 },
            router: { icon: '🌐', baseWidth: 60, baseHeight: 60, color: '#34d399', label: 'Router', isForwarder: true, maxPorts: 2 },
            switch: { icon: '↔️', baseWidth: 70, baseHeight: 40, color: '#fbbf24', label: 'Switch', isForwarder: true, maxPorts: Infinity },
            server: { icon: '🗄️', baseWidth: 60, baseHeight: 70, color: '#f59e0b', label: 'Server', isForwarder: false, maxPorts: Infinity }, // Server usually has one NIC, but can have more
            internet: { icon: '☁️', baseWidth: 70, baseHeight: 50, color: '#93c5fd', label: 'Internet', isForwarder: true, maxPorts: Infinity }
        };
        const deviceFontSize = 16;
        const labelFontSize = 10;
        const packetSize = 5;
        const packetSpeed = 0.015;
        const packetProcessingTime = 45; // Frames
        const connectionClickThreshold = 5; // Pixels for clicking on a line

        const tooltipElement = document.getElementById('tooltip');
        function showTooltip(text, x, y) {
            tooltipElement.textContent = text;
            tooltipElement.style.left = `${x + 15}px`;
            tooltipElement.style.top = `${y + 15}px`;
            tooltipElement.style.visibility = 'visible';
            tooltipElement.style.opacity = '1';
        }
        function hideTooltip() {
            tooltipElement.style.visibility = 'hidden';
            tooltipElement.style.opacity = '0';
        }

        function showMessage(message, duration = 3500) {
            messageOverlay.textContent = message;
            messageOverlay.style.display = 'block';
            setTimeout(() => {
                messageOverlay.style.display = 'none';
            }, duration);
        }

        function resizeCanvas() {
            canvas.width = canvasContainer.clientWidth;
            canvas.height = canvasContainer.clientHeight;
            redrawCanvas();
        }
        window.addEventListener('resize', resizeCanvas);

        // --- Drawing Functions ---
        function drawDevice(device) {
            ctx.font = `${deviceFontSize}px Arial`;
            const iconTextMetrics = ctx.measureText(device.icon);
            const iconWidth = iconTextMetrics.width;
            ctx.font = `${labelFontSize}px Arial`;
            const labelText = `${deviceProperties[device.type].label} ${device.id}`;
            const labelTextMetrics = ctx.measureText(labelText);
            const labelWidth = labelTextMetrics.width;

            device.width = Math.max(deviceProperties[device.type].baseWidth, iconWidth + 10, labelWidth + 10);
            device.height = deviceProperties[device.type].baseHeight + labelFontSize + 8;

            ctx.fillStyle = deviceProperties[device.type].color;
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.roundRect(device.x - device.width / 2, device.y - device.height / 2, device.width, device.height, [10]);
            ctx.fill();
            ctx.stroke();

            ctx.font = `${deviceFontSize}px Arial`;
            ctx.fillStyle = '#000000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(device.icon, device.x, device.y - (labelFontSize / 2) - 2);

            ctx.font = `${labelFontSize}px Arial`;
            ctx.fillStyle = '#000000';
            ctx.fillText(labelText, device.x, device.y + (device.height / 2) - (labelFontSize / 2) - 4);
        }

        function drawConnection(connection) {
            const fromDevice = devices.find(d => d.id === connection.fromDeviceId);
            const toDevice = devices.find(d => d.id === connection.toDeviceId);
            if (fromDevice && toDevice) {
                ctx.beginPath();
                ctx.moveTo(fromDevice.x, fromDevice.y);
                ctx.lineTo(toDevice.x, toDevice.y);
                ctx.strokeStyle = '#3b82f6';
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        }

        function drawPacket(packet) {
            ctx.beginPath();
            ctx.arc(packet.x, packet.y, packetSize, 0, 2 * Math.PI);
            if (packet.status === 'processing') {
                ctx.fillStyle = '#a0aec0';
                ctx.fill();
                ctx.font = `${packetSize * 1.5}px Arial`;
                ctx.fillStyle = 'white';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText("P", packet.x, packet.y + 1);
            } else {
                ctx.fillStyle = packet.color;
                ctx.fill();
            }
        }

        function redrawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            connections.forEach(drawConnection);
            devices.forEach(drawDevice);
            packets.forEach(drawPacket);

            if (isConnecting && firstDeviceForConnection) {
                ctx.beginPath();
                ctx.arc(firstDeviceForConnection.x, firstDeviceForConnection.y, (firstDeviceForConnection.width || deviceProperties[firstDeviceForConnection.type].baseWidth) / 2 + 5, 0, 2 * Math.PI);
                ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)';
                ctx.lineWidth = 3;
                ctx.stroke();
            }
            if (isSendingPacketMode && packetSourceDevice) {
                ctx.beginPath();
                ctx.arc(packetSourceDevice.x, packetSourceDevice.y, (packetSourceDevice.width || deviceProperties[packetSourceDevice.type].baseWidth) / 2 + 7, 0, 2 * Math.PI);
                ctx.strokeStyle = 'rgba(0, 255, 0, 0.6)';
                ctx.lineWidth = 3;
                ctx.stroke();
            }
        }

        // --- Event Handlers for Toolbar Buttons ---
        document.getElementById('addPc').addEventListener('click', () => setDeviceType('pc'));
        document.getElementById('addLaptop').addEventListener('click', () => setDeviceType('laptop'));
        document.getElementById('addRouter').addEventListener('click', () => setDeviceType('router'));
        document.getElementById('addSwitch').addEventListener('click', () => setDeviceType('switch'));
        document.getElementById('addServer').addEventListener('click', () => setDeviceType('server'));
        document.getElementById('addInternet').addEventListener('click', () => setDeviceType('internet'));

        document.getElementById('connectMode').addEventListener('click', toggleConnectMode);
        document.getElementById('packetSendMode').addEventListener('click', togglePacketSendMode);
        document.getElementById('deleteMode').addEventListener('click', toggleDeleteMode); // New
        document.getElementById('clearCanvas').addEventListener('click', () => {
            if (confirm('Möchten Sie die Arbeitsfläche wirklich leeren? Alle Elemente gehen verloren.')) {
                devices = []; connections = []; packets = [];
                nextDeviceId = 0; nextPacketId = 0;
                isConnecting = false; firstDeviceForConnection = null;
                isSendingPacketMode = false; packetSourceDevice = null;
                isDeletingMode = false; // Reset delete mode
                selectedDeviceType = null;
                stopAnimationLoop();
                updateToolbarButtons(); redrawCanvas();
            }
        });

        function updateToolbarButtons() {
            document.querySelectorAll('.toolbar-item').forEach(item => item.classList.remove('selected', 'border-blue-500'));
            if (selectedDeviceType) {
                let typeName = selectedDeviceType.charAt(0).toUpperCase() + selectedDeviceType.slice(1);
                if (selectedDeviceType === 'pc') typeName = 'Pc';
                // Ensure correct ID mapping for other types if needed, e.g. 'Laptop', 'Server'
                document.getElementById(`add${typeName}`)?.classList.add('selected', 'border-blue-500');
            }

            const connectBtn = document.getElementById('connectMode');
            connectBtn.textContent = isConnecting ? "Verbinden (aktiv)" : "Verbindungsmodus";
            isConnecting ? connectBtn.classList.add('button-success') : connectBtn.classList.remove('button-success');
            isConnecting ? connectBtn.classList.remove('button-secondary') : connectBtn.classList.add('button-secondary');

            const packetSendBtn = document.getElementById('packetSendMode');
            packetSendBtn.textContent = isSendingPacketMode ? "Paket senden (aktiv)" : "Paket senden";
            isSendingPacketMode ? packetSendBtn.classList.add('button-success') : packetSendBtn.classList.remove('button-success');
            isSendingPacketMode ? packetSendBtn.classList.remove('button-secondary') : packetSendBtn.classList.add('button-secondary');

            const deleteBtn = document.getElementById('deleteMode'); // New
            deleteBtn.textContent = isDeletingMode ? "Löschen (aktiv)" : "Löschen";
            isDeletingMode ? deleteBtn.classList.add('button-danger') : deleteBtn.classList.remove('button-danger');
            isDeletingMode ? deleteBtn.classList.remove('button-warning') : deleteBtn.classList.add('button-warning');
        }

        // --- Mode Setting Functions ---
        function setDeviceType(type) {
            selectedDeviceType = type;
            isConnecting = false; isSendingPacketMode = false; isDeletingMode = false;
            packetSourceDevice = null; firstDeviceForConnection = null;
            updateToolbarButtons(); redrawCanvas();
        }
        function toggleConnectMode() {
            isConnecting = !isConnecting;
            selectedDeviceType = null; isSendingPacketMode = false; isDeletingMode = false;
            packetSourceDevice = null;
            if (!isConnecting) firstDeviceForConnection = null;
            updateToolbarButtons(); redrawCanvas();
        }
        function togglePacketSendMode() {
            isSendingPacketMode = !isSendingPacketMode;
            selectedDeviceType = null; isConnecting = false; isDeletingMode = false;
            firstDeviceForConnection = null;
            if (!isSendingPacketMode) packetSourceDevice = null;
            updateToolbarButtons(); redrawCanvas();
        }
        function toggleDeleteMode() { // New
            isDeletingMode = !isDeletingMode;
            selectedDeviceType = null; isConnecting = false; isSendingPacketMode = false;
            firstDeviceForConnection = null; packetSourceDevice = null;
            updateToolbarButtons(); redrawCanvas();
        }


        // --- Canvas Event Handlers ---
        canvas.addEventListener('click', (event) => {
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            if (isDeletingMode) { // Handle delete mode first
                handleDeleteClick(x, y);
            } else if (selectedDeviceType) {
                addDevice(x, y, selectedDeviceType);
                selectedDeviceType = null; updateToolbarButtons();
            } else if (isConnecting) {
                handleConnectionClick(x, y);
            } else if (isSendingPacketMode) {
                handlePacketSendClick(x,y);
            }
        });

        canvas.addEventListener('mousedown', (event) => {
            if (isConnecting || selectedDeviceType || isSendingPacketMode || isDeletingMode) return; // Don't drag in active modes
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            for (let i = devices.length - 1; i >= 0; i--) {
                const device = devices[i];
                if (x >= device.x - device.width / 2 && x <= device.x + device.width / 2 &&
                    y >= device.y - device.height / 2 && y <= device.y + device.height / 2) {
                    draggingDevice = device;
                    dragOffsetX = x - device.x;
                    dragOffsetY = y - device.y;
                    canvas.style.cursor = 'grabbing';
                    return;
                }
            }
        });
        canvas.addEventListener('mousemove', (event) => {
            const rect = canvas.getBoundingClientRect();
            const mouseX = event.clientX - rect.left;
            const mouseY = event.clientY - rect.top;

            if (draggingDevice) {
                draggingDevice.x = mouseX - dragOffsetX;
                draggingDevice.y = mouseY - dragOffsetY;
                redrawCanvas();
            } else {
                let onDevice = false;
                for (let i = devices.length - 1; i >= 0; i--) {
                    const device = devices[i];
                    const dWidth = device.width || deviceProperties[device.type].baseWidth;
                    const dHeight = device.height || deviceProperties[device.type].baseHeight;
                    if (mouseX >= device.x - dWidth / 2 && mouseX <= device.x + dWidth / 2 &&
                        mouseY >= device.y - dHeight / 2 && mouseY <= device.y + dHeight / 2) {
                        showTooltip(`${deviceProperties[device.type].label} ${device.id}`, event.clientX, event.clientY);
                        canvas.style.cursor = isDeletingMode ? 'cell' : 'grab'; // Adjust cursor for delete mode
                        onDevice = true;
                        break;
                    }
                }
                
                let onPacket = false;
                if (!onDevice) {
                    const hoveredPacket = getPacketAt(mouseX, mouseY);
                    if (hoveredPacket) {
                        showTooltip(hoveredPacket.packetInfo, event.clientX, event.clientY);
                        canvas.style.cursor = 'help';
                        onPacket = true;
                    }
                }
                
                let onConnection = false;
                if(!onDevice && !onPacket && isDeletingMode) {
                    const hoveredConnection = getConnectionAt(mouseX, mouseY);
                    if(hoveredConnection){
                         showTooltip(`Verbindung zwischen Gerät ${hoveredConnection.fromDeviceId} und ${hoveredConnection.toDeviceId}`, event.clientX, event.clientY);
                         canvas.style.cursor = 'cell';
                         onConnection = true;
                    }
                }


                if (!onDevice && !onPacket && !onConnection) {
                    hideTooltip();
                    canvas.style.cursor = isDeletingMode ? 'cell' : (isConnecting || isSendingPacketMode ? 'crosshair' : (selectedDeviceType ? 'copy' : 'default'));
                }
            }
        });
        canvas.addEventListener('mouseup', () => {
            if (draggingDevice) {
                draggingDevice = null;
                canvas.style.cursor = isDeletingMode ? 'cell' : (isConnecting || isSendingPacketMode ? 'crosshair' : (selectedDeviceType ? 'copy' : 'default'));
            }
        });
        canvas.addEventListener('mouseout', () => {
            hideTooltip();
            if (draggingDevice) {
                draggingDevice = null;
                canvas.style.cursor = 'default';
            }
        });

        // --- Core Logic Functions ---
        function addDevice(x, y, type) {
            const deviceData = deviceProperties[type];
            if (!deviceData) return;
            const newDevice = {
                id: nextDeviceId++,
                type: type,
                x: x, y: y,
                icon: deviceData.icon,
                width:0, height:0
            };
            devices.push(newDevice);
            redrawCanvas();
        }
        
        function getDeviceConnectionCount(deviceId) {
            let count = 0;
            connections.forEach(conn => {
                if (conn.fromDeviceId === deviceId || conn.toDeviceId === deviceId) {
                    count++;
                }
            });
            return count;
        }


        function handleConnectionClick(x, y) {
            const clickedDevice = getDeviceAt(x, y);
            if (clickedDevice) {
                if (!firstDeviceForConnection) {
                    firstDeviceForConnection = clickedDevice;
                } else {
                    if (firstDeviceForConnection.id !== clickedDevice.id &&
                        !connectionExists(firstDeviceForConnection.id, clickedDevice.id)) {
                        
                        // Check port limits
                        const firstDeviceProps = deviceProperties[firstDeviceForConnection.type];
                        const clickedDeviceProps = deviceProperties[clickedDevice.type];

                        const firstDeviceConnections = getDeviceConnectionCount(firstDeviceForConnection.id);
                        const clickedDeviceConnections = getDeviceConnectionCount(clickedDevice.id);

                        if (firstDeviceConnections >= firstDeviceProps.maxPorts) {
                            showMessage(`${firstDeviceProps.label} ${firstDeviceForConnection.id} hat bereits die maximale Anzahl von ${firstDeviceProps.maxPorts} Verbindungen.`, 3000);
                            firstDeviceForConnection = null;
                            redrawCanvas();
                            return;
                        }
                        if (clickedDeviceConnections >= clickedDeviceProps.maxPorts) {
                            showMessage(`${clickedDeviceProps.label} ${clickedDevice.id} hat bereits die maximale Anzahl von ${clickedDeviceProps.maxPorts} Verbindungen.`, 3000);
                            firstDeviceForConnection = null; 
                            redrawCanvas();
                            return;
                        }
                        addConnection(firstDeviceForConnection.id, clickedDevice.id);
                    }
                    firstDeviceForConnection = null;
                }
                redrawCanvas();
            }
        }
        
        function handleDeleteClick(x, y) {
            const clickedDevice = getDeviceAt(x, y);
            if (clickedDevice) {
                // Remove device
                devices = devices.filter(d => d.id !== clickedDevice.id);
                // Remove connections to this device
                const oldConnectionsCount = connections.length;
                connections = connections.filter(conn => conn.fromDeviceId !== clickedDevice.id && conn.toDeviceId !== clickedDevice.id);
                if (connections.length < oldConnectionsCount) {
                     console.log(`Verbindungen für Gerät ${clickedDevice.id} entfernt.`);
                }
                // Remove packets related to this device
                packets = packets.filter(p => !p.path.includes(clickedDevice.id));

                showMessage(`${deviceProperties[clickedDevice.type].label} ${clickedDevice.id} und zugehörige Verbindungen/Pakete gelöscht.`, 2000);

            } else {
                // Try to delete a connection if no device was clicked
                const clickedConnection = getConnectionAt(x, y);
                if (clickedConnection) {
                    connections = connections.filter(conn =>
                        !( (conn.fromDeviceId === clickedConnection.fromDeviceId && conn.toDeviceId === clickedConnection.toDeviceId) ||
                           (conn.fromDeviceId === clickedConnection.toDeviceId && conn.toDeviceId === clickedConnection.fromDeviceId) )
                    );
                     // Remove packets currently on this segment
                    packets = packets.filter(packet => {
                        const sourceId = packet.path[packet.pathIndex];
                        const nextHopId = packet.path[packet.pathIndex + 1];
                        const isPacketOnSegment = (sourceId === clickedConnection.fromDeviceId && nextHopId === clickedConnection.toDeviceId) ||
                                                  (sourceId === clickedConnection.toDeviceId && nextHopId === clickedConnection.fromDeviceId);
                        return !isPacketOnSegment;
                    });
                    showMessage(`Verbindung zwischen Gerät ${clickedConnection.fromDeviceId} und ${clickedConnection.toDeviceId} gelöscht.`, 2000);
                }
            }
            redrawCanvas();
        }


        function handlePacketSendClick(x,y){
            const clickedDevice = getDeviceAt(x,y);
            if(clickedDevice){
                if(!packetSourceDevice){
                    packetSourceDevice = clickedDevice;
                } else {
                    if(packetSourceDevice.id === clickedDevice.id) {
                        showMessage("Quelle und Ziel dürfen nicht identisch sein.", 2000);
                        packetSourceDevice = null;
                        redrawCanvas();
                        return;
                    }

                    const pathDeviceIds = findPath(packetSourceDevice, clickedDevice, devices, connections);
                    
                    if(pathDeviceIds && pathDeviceIds.length > 0){
                        createPacket(packetSourceDevice, clickedDevice, pathDeviceIds);
                    } else {
                       showMessage(`Kein gültiger Pfad von ${deviceProperties[packetSourceDevice.type].label} ${packetSourceDevice.id} zu ${deviceProperties[clickedDevice.type].label} ${clickedDevice.id} gefunden.`);
                    }
                    packetSourceDevice = null;
                }
                redrawCanvas();
            }
        }

        function getDeviceAt(x, y) {
            for (let i = devices.length - 1; i >= 0; i--) {
                const device = devices[i];
                const dWidth = device.width || deviceProperties[device.type].baseWidth;
                const dHeight = device.height || deviceProperties[device.type].baseHeight;
                if (x >= device.x - dWidth / 2 && x <= device.x + dWidth / 2 &&
                    y >= device.y - dHeight / 2 && y <= device.y + dHeight / 2) {
                    return device;
                }
            }
            return null;
        }
        
        function getPacketAt(x, y) {
            for (let i = packets.length - 1; i >= 0; i--) {
                const packet = packets[i];
                const distanceSquared = (x - packet.x) * (x - packet.x) + (y - packet.y) * (y - packet.y);
                if (distanceSquared <= (packetSize * packetSize * 1.5 * 1.5) ) { // Increased click radius for packets
                    return packet;
                }
            }
            return null;
        }
        
        // New function to find a connection at a given point (x,y)
        function getConnectionAt(clickX, clickY) {
            for (const conn of connections) {
                const fromDevice = devices.find(d => d.id === conn.fromDeviceId);
                const toDevice = devices.find(d => d.id === conn.toDeviceId);
                if (!fromDevice || !toDevice) continue;

                const x1 = fromDevice.x;
                const y1 = fromDevice.y;
                const x2 = toDevice.x;
                const y2 = toDevice.y;

                // Distance from point (clickX, clickY) to line segment (x1,y1)-(x2,y2)
                const lenSq = (x2 - x1) * (x2 - x1) + (y2 - y1) * (y2 - y1);
                if (lenSq === 0) { // Start and end points are the same
                     const distToPointSq = (clickX - x1) * (clickX - x1) + (clickY - y1) * (clickY - y1);
                     if (Math.sqrt(distToPointSq) < connectionClickThreshold) return conn;
                     continue;
                }

                let t = ((clickX - x1) * (x2 - x1) + (clickY - y1) * (y2 - y1)) / lenSq;
                t = Math.max(0, Math.min(1, t)); // Clamp t to the segment

                const closestX = x1 + t * (x2 - x1);
                const closestY = y1 + t * (y2 - y1);

                const distSq = (clickX - closestX) * (clickX - closestX) + (clickY - closestY) * (clickY - closestY);

                if (Math.sqrt(distSq) < connectionClickThreshold) {
                    return conn;
                }
            }
            return null;
        }


        function connectionExists(devId1, devId2) {
            return connections.some(conn =>
                (conn.fromDeviceId === devId1 && conn.toDeviceId === devId2) ||
                (conn.fromDeviceId === devId2 && conn.toDeviceId === devId1)
            );
        }

        function addConnection(fromId, toId) {
            connections.push({ fromDeviceId: fromId, toDeviceId: toId });
            redrawCanvas();
        }

        // --- Pathfinding (BFS) and Packet Logic ---
        function getNeighbors(deviceId, allDevices, allConnections) {
            const neighbors = [];
            const deviceMap = new Map(allDevices.map(d => [d.id, d]));
            for (const conn of allConnections) {
                if (conn.fromDeviceId === deviceId) {
                    const neighbor = deviceMap.get(conn.toDeviceId);
                    if (neighbor) neighbors.push(neighbor);
                } else if (conn.toDeviceId === deviceId) {
                    const neighbor = deviceMap.get(conn.fromDeviceId);
                    if (neighbor) neighbors.push(neighbor);
                }
            }
            return neighbors;
        }

        function findPath(startDevice, endDevice, allDevices, allConnections) {
            if (!startDevice || !endDevice || startDevice.id === endDevice.id) return null;

            const queue = [];
            const visitedPaths = new Map(); 
            const deviceMap = new Map(allDevices.map(d => [d.id, d]));

            const initialPath = [startDevice.id];
            queue.push(initialPath);
            visitedPaths.set(startDevice.id, initialPath);

            while (queue.length > 0) {
                const currentPathArray = queue.shift();
                const currentDeviceId = currentPathArray[currentPathArray.length - 1];
                const currentDevice = deviceMap.get(currentDeviceId);

                if (!currentDevice) continue;

                if (currentDeviceId === endDevice.id) {
                    return currentPathArray;
                }

                let canExploreFromCurrent = deviceProperties[currentDevice.type].isForwarder || currentDevice.id === startDevice.id;
                
                if (canExploreFromCurrent) {
                    const neighbors = getNeighbors(currentDeviceId, allDevices, allConnections);
                    for (const neighborDevice of neighbors) {
                        if (!visitedPaths.has(neighborDevice.id)) { 
                            let canHopToNeighbor = true;

                            if (currentDevice.type === 'internet' && neighborDevice.type !== 'router') {
                                canHopToNeighbor = false;
                            }
                            if (neighborDevice.type === 'internet' && currentDevice.type !== 'router') {
                                canHopToNeighbor = false;
                            }
                            
                            if (canHopToNeighbor) {
                                const newPathArray = [...currentPathArray, neighborDevice.id];
                                visitedPaths.set(neighborDevice.id, newPathArray);
                                queue.push(newPathArray);
                            }
                        }
                    }
                }
            }
            return null;
        }


        function createPacket(initialSourceDevice, finalDestinationDevice, pathDeviceIds) {
            let sourceIp = `192.168.1.${initialSourceDevice.id}`;
            if (initialSourceDevice.type === 'internet') sourceIp = `203.0.113.${initialSourceDevice.id}`;

            let destinationIp = `192.168.1.${finalDestinationDevice.id}`;
            if (finalDestinationDevice.type === 'internet') {
                 destinationIp = `8.8.8.8`; 
            } else {
                 destinationIp = `192.168.1.${finalDestinationDevice.id}`;
            }


            const protocol = 'SIM_ICMP';
            const packetInfoText = `P${nextPacketId}: ${sourceIp} -> ${destinationIp} (${protocol})`;

            const newPacket = {
                id: nextPacketId++,
                path: pathDeviceIds,
                pathIndex: 0,
                finalDestinationDeviceId: finalDestinationDevice.id,
                x: initialSourceDevice.x,
                y: initialSourceDevice.y,
                progress: 0,
                speed: packetSpeed,
                color: `hsl(${Math.random() * 360}, 100%, 50%)`,
                status: 'traveling',
                processingTimer: 0,
                sourceIp: sourceIp,
                destinationIp: destinationIp,
                protocol: protocol,
                packetInfo: packetInfoText
            };
            packets.push(newPacket);
            startAnimationLoop();
        }

        function updatePackets() {
            for (let i = packets.length - 1; i >= 0; i--) {
                const packet = packets[i];
                const currentSegmentSourceDevice = devices.find(d => d.id === packet.path[packet.pathIndex]);
                const currentSegmentNextHopDevice = devices.find(d => d.id === packet.path[packet.pathIndex + 1]);

                if (!currentSegmentSourceDevice || !currentSegmentNextHopDevice) {
                    console.warn(`Paket ${packet.id} hat ungültiges Segment (Gerät nicht gefunden oder Verbindung gelöscht), wird entfernt.`);
                    packets.splice(i, 1);
                    continue;
                }
                
                if (packet.status === 'processing') {
                    packet.processingTimer--;
                    if (packet.processingTimer <= 0) {
                        packet.status = 'traveling';
                        packet.color = `hsl(${Math.random() * 360}, 100%, 50%)`;
                    }
                } else if (packet.status === 'traveling') {
                    packet.progress += packet.speed;
                    if (packet.progress >= 1) {
                        packet.progress = 1;
                        
                        if (currentSegmentNextHopDevice.id === packet.finalDestinationDeviceId) {
                            packets.splice(i, 1);
                            showMessage(`Paket ${packet.id} hat ${deviceProperties[currentSegmentNextHopDevice.type].label} ${currentSegmentNextHopDevice.id} erreicht!`, 2000);
                        } else {
                            packet.status = 'processing';
                            packet.processingTimer = packetProcessingTime;
                            packet.x = currentSegmentNextHopDevice.x;
                            packet.y = currentSegmentNextHopDevice.y;
                            packet.pathIndex++;
                            packet.progress = 0;
                        }
                    } else {
                        packet.x = currentSegmentSourceDevice.x + (currentSegmentNextHopDevice.x - currentSegmentSourceDevice.x) * packet.progress;
                        packet.y = currentSegmentSourceDevice.y + (currentSegmentNextHopDevice.y - currentSegmentSourceDevice.y) * packet.progress;
                    }
                }
            }
        }

        // --- Animation Loop ---
        function startAnimationLoop() {
            if (!animationFrameId) {
                animatePackets();
            }
        }
        function stopAnimationLoop() {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
        }
        function animatePackets() {
            updatePackets();
            redrawCanvas();
            // Keep animation running if there are packets OR if any mode is active that might require redraws (like selection highlights)
            if (packets.length > 0 || isConnecting || isSendingPacketMode || isDeletingMode || selectedDeviceType) {
                animationFrameId = requestAnimationFrame(animatePackets);
            } else {
                animationFrameId = null;
                console.log("Keine aktiven Pakete oder UI-Aktionen, Animationsloop gestoppt.");
            }
        }

        // --- Initial Setup ---
        resizeCanvas();
        updateToolbarButtons();
        console.log("Netzwerk-Diagrammersteller mit erweiterten Funktionen initialisiert.");
    </script>
</body>
</html>