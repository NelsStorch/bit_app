<!--
  Datei: plan2.php
  Beschreibung: Erweiterte Version des Netzwerk-Diagrammerstellers.
  Bietet zusätzlich Undo/Redo-Funktionalität, Kontextmenüs (Rechtsklick)
  und detaillierte Konfigurationsmöglichkeiten für Geräte (Hostname, IP) und Verbindungen (Typ).

  Technologien: HTML5 Canvas, JavaScript, Tailwind CSS
-->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netzwerk-Diagrammersteller mit erweiterten Funktionen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; overflow: hidden; }
        .toolbar { display: flex; flex-wrap: wrap; }
        .toolbar-item { cursor: pointer; transition: background-color 0.3s; min-width: 70px; }
        .toolbar-item:hover { background-color: #e0e0e0; }
        .toolbar-item.selected { background-color: #bfdbfe; border-color: #3b82f6; }
        #networkCanvas { border: 1px solid #ccc; background-color: #f9f9f9; display: block; }
        .button-std { padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-weight: 500; transition: background-color 0.2s, border-color 0.2s, color 0.2s; border: 1px solid transparent; font-size: 0.875rem; }
        .button-primary { background-color: #3b82f6; color: white; }
        .button-primary:hover { background-color: #2563eb; }
        .button-secondary { background-color: #6b7280; color: white; }
        .button-secondary:hover { background-color: #4b5563; }
        .button-success { background-color: #10b981; color: white; }
        .button-success:hover { background-color: #059669; }
        .button-danger { background-color: #ef4444; color: white; }
        .button-danger:hover { background-color: #dc2626; }
        .button-warning { background-color: #f59e0b; color: white; }
        .button-warning:hover { background-color: #d97706; }
        .device-icon { font-size: 20px; text-align: center; line-height: 1; }
        .tooltip { position: absolute; background-color: #333; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; visibility: hidden; opacity: 0; transition: opacity 0.2s; z-index: 10000; pointer-events: none; }
        .message-overlay { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: rgba(0,0,0,0.75); color: white; padding: 10px 20px; border-radius: 8px; z-index: 10100; font-size: 14px; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* Kontextmenü Styles */
        .context-menu { position: absolute; background-color: white; border: 1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,0.15); z-index: 1000; min-width: 150px; border-radius: 4px; padding: 4px 0; }
        .context-menu-item { padding: 8px 12px; cursor: pointer; font-size: 14px; }
        .context-menu-item:hover { background-color: #f0f0f0; }
        .context-menu-separator { height: 1px; background-color: #e0e0e0; margin: 4px 0; }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 20000; visibility: hidden; opacity: 0; transition: opacity 0.3s, visibility 0.3s; }
        .modal-overlay.active { visibility: visible; opacity: 1; }
        .modal-content { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.2); min-width: 300px; max-width: 90%; }
        .modal-content h3 { margin-top: 0; margin-bottom: 15px; font-size: 1.25rem; }
        .modal-content label { display: block; margin-bottom: 5px; font-weight: 500; }
        .modal-content input[type="text"], .modal-content select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .modal-actions { text-align: right; margin-top: 15px; }
        .modal-actions button { margin-left: 10px; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 flex flex-col h-screen">

    <!-- Werkzeugleiste mit Undo/Redo Buttons -->
    <div class="bg-white shadow-md p-2 flex items-center space-x-1 print:hidden toolbar">
        <div id="addPc" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="PC hinzufügen">
            <div class="device-icon">💻</div><span class="text-xs mt-1">PC</span>
        </div>
        <div id="addLaptop" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Laptop hinzufügen">
            <div class="device-icon">🖥️</div><span class="text-xs mt-1">Laptop</span>
        </div>
        <div id="addRouter" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Router hinzufügen">
            <div class="device-icon">🌐</div><span class="text-xs mt-1">Router</span>
        </div>
        <div id="addSwitch" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Switch hinzufügen">
            <div class="device-icon">↔️</div><span class="text-xs mt-1">Switch</span>
        </div>
        <div id="addServer" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Server hinzufügen">
            <div class="device-icon">🗄️</div><span class="text-xs mt-1">Server</span>
        </div>
        <div id="addInternet" class="toolbar-item p-2 border rounded-lg flex flex-col items-center" title="Internet hinzufügen">
            <div class="device-icon">☁️</div><span class="text-xs mt-1">Internet</span>
        </div>
        <div class="flex-grow"></div>
        <button id="undoButton" class="button-std button-secondary" title="Rückgängig (Ctrl+Z)">↩️</button>
        <button id="redoButton" class="button-std button-secondary" title="Wiederherstellen (Ctrl+Y)">↪️</button>
        <button id="connectMode" class="button-std button-secondary">Verbinden</button>
        <button id="packetSendMode" class="button-std button-secondary">Paket</button>
        <button id="deleteMode" class="button-std button-warning">Löschen</button>
        <button id="clearCanvas" class="button-std button-danger">Leeren</button>
    </div>

    <!-- Zeichenfläche -->
    <div class="flex-grow p-2 relative" id="canvasContainer">
        <canvas id="networkCanvas"></canvas>
        <div id="messageOverlay" class="message-overlay"></div>
        <!-- Kontextmenü Container -->
        <div id="contextMenu" class="context-menu" style="display: none;"></div>
    </div>

    <div id="tooltip" class="tooltip"></div>

    <!-- Modal für Gerätekonfiguration -->
    <div id="deviceConfigModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="deviceConfigTitle">Gerätekonfiguration</h3>
            <div>
                <label for="deviceHostname">Hostname:</label>
                <input type="text" id="deviceHostname">
            </div>
            <div>
                <label for="deviceIpAddress">IP-Adresse:</label>
                <input type="text" id="deviceIpAddress">
            </div>
            <div class="modal-actions">
                <button id="cancelDeviceConfig" class="button-std button-secondary">Abbrechen</button>
                <button id="saveDeviceConfig" class="button-std button-primary">Speichern</button>
            </div>
        </div>
    </div>

    <!-- Modal für Verbindungskonfiguration -->
    <div id="connectionConfigModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="connectionConfigTitle">Verbindungstyp</h3>
            <div>
                <label for="connectionTypeSelect">Typ:</label>
                <select id="connectionTypeSelect">
                    <option value="ethernet">Ethernet (Kabel)</option>
                    <option value="wifi">WLAN (Drahtlos)</option>
                    <option value="fiber">Glasfaser</option>
                </select>
            </div>
            <div class="modal-actions">
                <button id="cancelConnectionConfig" class="button-std button-secondary">Abbrechen</button>
                <button id="saveConnectionConfig" class="button-std button-primary">Speichern</button>
            </div>
        </div>
    </div>


    <script>
        // --- Globale Variablen und Konstanten ---
        const canvas = document.getElementById('networkCanvas');
        const ctx = canvas.getContext('2d');
        const canvasContainer = document.getElementById('canvasContainer');
        const messageOverlay = document.getElementById('messageOverlay');
        const contextMenuElement = document.getElementById('contextMenu');
        const deviceConfigModal = document.getElementById('deviceConfigModal');
        const connectionConfigModal = document.getElementById('connectionConfigModal');

        let devices = [];
        let connections = [];
        let packets = [];
        let nextDeviceId = 0;
        let nextPacketId = 0;

        // Modi und Zustände
        let selectedDeviceType = null;
        let isConnecting = false;
        let firstDeviceForConnection = null;
        let isSendingPacketMode = false;
        let packetSourceDevice = null;
        let isDeletingMode = false;
        let draggingDevice = null;
        let dragOffsetX, dragOffsetY;
        let animationFrameId = null;

        let currentConfiguringDevice = null; // Für Geräte-Modal
        let currentConfiguringConnection = null; // Für Verbindungs-Modal

        // Undo/Redo Stacks
        const undoStack = [];
        const redoStack = [];
        const MAX_UNDO_STEPS = 30;


        const deviceProperties = {
            pc: { icon: '💻', baseWidth: 50, baseHeight: 50, color: '#60a5fa', label: 'PC', isForwarder: false, maxPorts: 1, defaultIpPrefix: '192.168.1.' },
            laptop: { icon: '🖥️', baseWidth: 50, baseHeight: 45, color: '#a78bfa', label: 'Laptop', isForwarder: false, maxPorts: 1, defaultIpPrefix: '192.168.1.' },
            router: { icon: '🌐', baseWidth: 60, baseHeight: 60, color: '#34d399', label: 'Router', isForwarder: true, maxPorts: 2, defaultIpPrefix: '192.168.0.' },
            switch: { icon: '↔️', baseWidth: 70, baseHeight: 40, color: '#fbbf24', label: 'Switch', isForwarder: true, maxPorts: 8, defaultIpPrefix: '192.168.1.' },
            server: { icon: '🗄️', baseWidth: 60, baseHeight: 70, color: '#f59e0b', label: 'Server', isForwarder: false, maxPorts: 2, defaultIpPrefix: '10.0.0.' },
            internet: { icon: '☁️', baseWidth: 70, baseHeight: 50, color: '#93c5fd', label: 'Internet', isForwarder: true, maxPorts: Infinity, defaultIpPrefix: '203.0.113.' }
        };
        const connectionTypes = {
            ethernet: { color: '#3b82f6', lineDash: [] },
            wifi: { color: '#10b981', lineDash: [5, 5] },
            fiber: { color: '#ef4444', lineDash: [10, 2, 2, 2] }
        };

        const deviceFontSize = 16;
        const labelFontSize = 10;
        const portInfoFontSize = 9;
        const packetSize = 5;
        const packetSpeed = 0.015;
        const packetProcessingTime = 45; // Frames
        const connectionClickThreshold = 8; // Pixel

        const tooltipElement = document.getElementById('tooltip');

        // --- Undo/Redo Logik ---

        /** Speichert den aktuellen Zustand im Undo-Stack */
        function saveState() {
            const state = {
                devices: JSON.parse(JSON.stringify(devices)),
                connections: JSON.parse(JSON.stringify(connections)),
                nextDeviceId: nextDeviceId,
                nextPacketId: nextPacketId 
            };
            undoStack.push(state);
            if (undoStack.length > MAX_UNDO_STEPS) {
                undoStack.shift(); 
            }
            redoStack.length = 0; 
            updateUndoRedoButtons();
        }

        /** Stellt den letzten Zustand wieder her */
        function undo() {
            if (undoStack.length > 0) {
                const currentState = { 
                    devices: JSON.parse(JSON.stringify(devices)),
                    connections: JSON.parse(JSON.stringify(connections)),
                    nextDeviceId: nextDeviceId,
                    nextPacketId: nextPacketId
                };
                redoStack.push(currentState);

                const prevState = undoStack.pop();
                devices = JSON.parse(JSON.stringify(prevState.devices));
                connections = JSON.parse(JSON.stringify(prevState.connections));
                nextDeviceId = prevState.nextDeviceId;
                nextPacketId = prevState.nextPacketId;
                
                resetModesAndSelections();
                redrawCanvas();
                updateUndoRedoButtons();
            }
        }

        function redo() {
            if (redoStack.length > 0) {
                 const currentState = { 
                    devices: JSON.parse(JSON.stringify(devices)),
                    connections: JSON.parse(JSON.stringify(connections)),
                    nextDeviceId: nextDeviceId,
                    nextPacketId: nextPacketId
                };
                undoStack.push(currentState);

                const nextState = redoStack.pop();
                devices = JSON.parse(JSON.stringify(nextState.devices));
                connections = JSON.parse(JSON.stringify(nextState.connections));
                nextDeviceId = nextState.nextDeviceId;
                nextPacketId = nextState.nextPacketId;

                resetModesAndSelections();
                redrawCanvas();
                updateUndoRedoButtons();
            }
        }
        
        function updateUndoRedoButtons() {
            document.getElementById('undoButton').disabled = undoStack.length === 0;
            document.getElementById('redoButton').disabled = redoStack.length === 0;
        }

        function resetModesAndSelections() {
            selectedDeviceType = null;
            isConnecting = false;
            firstDeviceForConnection = null;
            isSendingPacketMode = false;
            packetSourceDevice = null;
            isDeletingMode = false;
            draggingDevice = null;
            currentConfiguringDevice = null;
            currentConfiguringConnection = null;
            hideContextMenu();
            hideDeviceConfigModal();
            hideConnectionConfigModal();
            updateToolbarButtons();
        }


        // --- Tooltip und Nachrichten ---
        function showTooltip(text, x, y) {
            tooltipElement.innerHTML = text; 
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

        // --- Canvas Zeichenfunktionen ---
        function resizeCanvas() {
            canvas.width = canvasContainer.clientWidth;
            canvas.height = canvasContainer.clientHeight;
            redrawCanvas();
        }
        window.addEventListener('resize', resizeCanvas);

        function drawDevice(device) {
            ctx.font = `${deviceFontSize}px Arial`;
            const iconTextMetrics = ctx.measureText(device.icon);
            const iconWidth = iconTextMetrics.width;
            
            const hostname = device.hostname || `${deviceProperties[device.type].label}-${device.id}`;
            ctx.font = `${labelFontSize}px Arial`;
            const labelTextMetrics = ctx.measureText(hostname);
            const labelWidth = labelTextMetrics.width;

            device.width = Math.max(deviceProperties[device.type].baseWidth, iconWidth + 10, labelWidth + 10);
            device.height = deviceProperties[device.type].baseHeight + labelFontSize + 8 + (portInfoFontSize + 4); 

            // Geräte-Körper
            ctx.fillStyle = deviceProperties[device.type].color;
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.roundRect(device.x - device.width / 2, device.y - device.height / 2, device.width, device.height, [10]);
            ctx.fill();
            ctx.stroke();

            // Icon
            const iconYOffset = -(labelFontSize / 2) - 2 - (portInfoFontSize / 2);
            ctx.font = `${deviceFontSize}px Arial`;
            ctx.fillStyle = '#000000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(device.icon, device.x, device.y + iconYOffset);

            // Hostname
            const hostnameYOffset = iconYOffset + (deviceFontSize / 2) + (labelFontSize / 2) + 4;
            ctx.font = `${labelFontSize}px Arial`;
            ctx.fillStyle = '#000000';
            ctx.fillText(hostname, device.x, device.y + hostnameYOffset);
            
            // Port Infos
            const props = deviceProperties[device.type];
            const currentConnections = getDeviceConnectionCount(device.id);
            const portText = `Ports: ${currentConnections}/${props.maxPorts === Infinity ? '∞' : props.maxPorts}`;
            ctx.font = `${portInfoFontSize}px Arial`;
            ctx.fillStyle = '#333333';
            const portInfoYOffset = hostnameYOffset + (labelFontSize/2) + (portInfoFontSize/2) + 2;
            ctx.fillText(portText, device.x, device.y + portInfoYOffset);
        }

        function drawConnection(connection) {
            const fromDevice = devices.find(d => d.id === connection.fromDeviceId);
            const toDevice = devices.find(d => d.id === connection.toDeviceId);
            if (fromDevice && toDevice) {
                const type = connection.type || 'ethernet';
                const style = connectionTypes[type] || connectionTypes.ethernet;

                ctx.beginPath();
                ctx.moveTo(fromDevice.x, fromDevice.y);
                ctx.lineTo(toDevice.x, toDevice.y);
                ctx.strokeStyle = style.color;
                ctx.lineWidth = 2;
                ctx.setLineDash(style.lineDash);
                ctx.stroke();
                ctx.setLineDash([]); 
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

        // --- Toolbar Button Event Handler ---
        document.getElementById('addPc').addEventListener('click', () => setDeviceType('pc'));
        document.getElementById('addLaptop').addEventListener('click', () => setDeviceType('laptop'));
        document.getElementById('addRouter').addEventListener('click', () => setDeviceType('router'));
        document.getElementById('addSwitch').addEventListener('click', () => setDeviceType('switch'));
        document.getElementById('addServer').addEventListener('click', () => setDeviceType('server'));
        document.getElementById('addInternet').addEventListener('click', () => setDeviceType('internet'));

        document.getElementById('undoButton').addEventListener('click', undo);
        document.getElementById('redoButton').addEventListener('click', redo);
        document.getElementById('connectMode').addEventListener('click', toggleConnectMode);
        document.getElementById('packetSendMode').addEventListener('click', togglePacketSendMode);
        document.getElementById('deleteMode').addEventListener('click', toggleDeleteMode);
        document.getElementById('clearCanvas').addEventListener('click', () => {
            if (confirm('Möchten Sie die Arbeitsfläche wirklich leeren? Alle Elemente gehen verloren.')) {
                saveState(); 
                devices = []; connections = []; packets = [];
                nextDeviceId = 0; nextPacketId = 0;
                resetModesAndSelections();
                stopAnimationLoop();
                redrawCanvas();
            }
        });

        function updateToolbarButtons() {
            document.querySelectorAll('.toolbar-item').forEach(item => item.classList.remove('selected', 'border-blue-500'));
            if (selectedDeviceType) {
                let typeName = selectedDeviceType.charAt(0).toUpperCase() + selectedDeviceType.slice(1);
                if (selectedDeviceType === 'pc') typeName = 'Pc';
                document.getElementById(`add${typeName}`)?.classList.add('selected', 'border-blue-500');
            }

            const connectBtn = document.getElementById('connectMode');
            connectBtn.textContent = isConnecting ? "Verbinden (aktiv)" : "Verbinden";
            isConnecting ? connectBtn.classList.add('button-success') : connectBtn.classList.remove('button-success');
            isConnecting ? connectBtn.classList.remove('button-secondary') : connectBtn.classList.add('button-secondary');

            const packetSendBtn = document.getElementById('packetSendMode');
            packetSendBtn.textContent = isSendingPacketMode ? "Paket (aktiv)" : "Paket";
            isSendingPacketMode ? packetSendBtn.classList.add('button-success') : packetSendBtn.classList.remove('button-success');
            isSendingPacketMode ? packetSendBtn.classList.remove('button-secondary') : packetSendBtn.classList.add('button-secondary');

            const deleteBtn = document.getElementById('deleteMode');
            deleteBtn.textContent = isDeletingMode ? "Löschen (aktiv)" : "Löschen";
            isDeletingMode ? deleteBtn.classList.add('button-danger') : deleteBtn.classList.remove('button-danger');
            isDeletingMode ? deleteBtn.classList.remove('button-warning') : deleteBtn.classList.add('button-warning');
            updateUndoRedoButtons();
        }

        // --- Modus-Funktionen ---
        function setDeviceType(type) {
            selectedDeviceType = type;
            isConnecting = false; isSendingPacketMode = false; isDeletingMode = false;
            packetSourceDevice = null; firstDeviceForConnection = null;
            hideContextMenu();
            updateToolbarButtons(); redrawCanvas();
        }
        function toggleConnectMode() {
            isConnecting = !isConnecting;
            selectedDeviceType = null; isSendingPacketMode = false; isDeletingMode = false;
            packetSourceDevice = null;
            if (!isConnecting) firstDeviceForConnection = null;
            hideContextMenu();
            updateToolbarButtons(); redrawCanvas();
        }
        function togglePacketSendMode() {
            isSendingPacketMode = !isSendingPacketMode;
            selectedDeviceType = null; isConnecting = false; isDeletingMode = false;
            firstDeviceForConnection = null;
            if (!isSendingPacketMode) packetSourceDevice = null;
            hideContextMenu();
            updateToolbarButtons(); redrawCanvas();
        }
        function toggleDeleteMode() {
            isDeletingMode = !isDeletingMode;
            selectedDeviceType = null; isConnecting = false; isSendingPacketMode = false;
            firstDeviceForConnection = null; packetSourceDevice = null;
            hideContextMenu();
            updateToolbarButtons(); redrawCanvas();
        }

        // --- Canvas Event Handler (Klick, Mausbewegung, Kontextmenü) ---
        canvas.addEventListener('click', (event) => {
            if (event.button !== 0) return; 

            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            hideContextMenu(); 

            if (isDeletingMode) {
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
            if (event.button !== 0) return; 
            if (isConnecting || selectedDeviceType || isSendingPacketMode || isDeletingMode) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            hideContextMenu();

            for (let i = devices.length - 1; i >= 0; i--) {
                const device = devices[i];
                if (x >= device.x - device.width / 2 && x <= device.x + device.width / 2 &&
                    y >= device.y - device.height / 2 && y <= device.y + device.height / 2) {
                    draggingDevice = device;
                    draggingDevice.initialX = device.x;
                    draggingDevice.initialY = device.y;
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
                let onElement = false; 
                const hoveredDevice = getDeviceAt(mouseX, mouseY);
                if (hoveredDevice) {
                    const hostname = hoveredDevice.hostname || `${deviceProperties[hoveredDevice.type].label}-${hoveredDevice.id}`;
                    const ip = hoveredDevice.ipAddress || deviceProperties[hoveredDevice.type].defaultIpPrefix + hoveredDevice.id;
                    showTooltip(`<b>${hostname}</b><br>Typ: ${deviceProperties[hoveredDevice.type].label}<br>IP: ${ip}`, event.clientX, event.clientY);
                    canvas.style.cursor = isDeletingMode ? 'cell' : ( isConnecting || isSendingPacketMode ? 'crosshair' : 'grab');
                    onElement = true;
                }

                if (!onElement) {
                    const hoveredPacket = getPacketAt(mouseX, mouseY);
                    if (hoveredPacket) {
                        const sourceDev = devices.find(d => d.id === hoveredPacket.path[0]);
                        const destDev = devices.find(d => d.id === hoveredPacket.finalDestinationDeviceId);
                        const currentHopDev = devices.find(d => d.id === hoveredPacket.path[hoveredPacket.pathIndex]);
                        const nextHopDev = devices.find(d => d.id === hoveredPacket.path[hoveredPacket.pathIndex + 1]);

                        let tooltipText = `<b>Paket ${hoveredPacket.id}</b><br>Status: ${hoveredPacket.status === 'traveling' ? 'Unterwegs' : 'Verarbeitung'}<br>`;
                        tooltipText += `Von: ${hoveredPacket.sourceIp} (${sourceDev?.hostname || 'Unbekannt'})<br>`;
                        tooltipText += `Nach: ${hoveredPacket.destinationIp} (${destDev?.hostname || 'Unbekannt'})<br>`;
                        if (currentHopDev) tooltipText += `Akt. Hop: ${currentHopDev.hostname || `Gerät ${currentHopDev.id}`}<br>`;
                        if (nextHopDev) tooltipText += `Nächst. Hop: ${nextHopDev.hostname || `Gerät ${nextHopDev.id}`}`;
                        
                        showTooltip(tooltipText, event.clientX, event.clientY);
                        canvas.style.cursor = 'help';
                        onElement = true;
                    }
                }
                
                if(!onElement && (isDeletingMode || isConnecting)) { 
                    const hoveredConnection = getConnectionAt(mouseX, mouseY);
                    if(hoveredConnection){
                         const fromDev = devices.find(d => d.id === hoveredConnection.fromDeviceId);
                         const toDev = devices.find(d => d.id === hoveredConnection.toDeviceId);
                         const typeName = Object.keys(connectionTypes).find(k => connectionTypes[k] === connectionTypes[hoveredConnection.type || 'ethernet']) || hoveredConnection.type || 'Ethernet';
                         showTooltip(`Verbindung (${typeName})<br>Zwischen: ${fromDev?.hostname || `Gerät ${fromDev?.id}`} & ${toDev?.hostname || `Gerät ${toDev?.id}`}`, event.clientX, event.clientY);
                         canvas.style.cursor = isDeletingMode ? 'cell' : 'crosshair';
                         onElement = true;
                    }
                }

                if (!onElement) {
                    hideTooltip();
                    canvas.style.cursor = isDeletingMode ? 'cell' : (isConnecting || isSendingPacketMode ? 'crosshair' : (selectedDeviceType ? 'copy' : 'default'));
                }
            }
        });

        canvas.addEventListener('mouseup', (event) => {
            if (event.button !== 0) return; 
            if (draggingDevice) {
                if (draggingDevice.x !== draggingDevice.initialX || draggingDevice.y !== draggingDevice.initialY) {
                    saveState(); 
                }
                delete draggingDevice.initialX; 
                delete draggingDevice.initialY;
                draggingDevice = null;
                canvas.style.cursor = isDeletingMode ? 'cell' : (isConnecting || isSendingPacketMode ? 'crosshair' : (selectedDeviceType ? 'copy' : 'default'));
                redrawCanvas(); 
            }
        });

        canvas.addEventListener('mouseout', () => { 
            hideTooltip();
            if (draggingDevice) {
                if (draggingDevice.x !== draggingDevice.initialX || draggingDevice.y !== draggingDevice.initialY) {
                    saveState();
                }
                delete draggingDevice.initialX;
                delete draggingDevice.initialY;
                draggingDevice = null;
                canvas.style.cursor = 'default';
                redrawCanvas();
            }
        });
        
        // --- Kontextmenü Logik ---
        canvas.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            const clickedDevice = getDeviceAt(x, y);
            const clickedConnection = !clickedDevice ? getConnectionAt(x, y) : null;

            if (clickedDevice) {
                showContextMenu(event.clientX, event.clientY, 'device', clickedDevice);
            } else if (clickedConnection) {
                showContextMenu(event.clientX, event.clientY, 'connection', clickedConnection);
            } else {
                hideContextMenu();
            }
        });

        function showContextMenu(mouseX, mouseY, type, item) {
            contextMenuElement.innerHTML = ''; 
            contextMenuElement.style.left = `${mouseX}px`;
            contextMenuElement.style.top = `${mouseY}px`;
            contextMenuElement.style.display = 'block';

            if (type === 'device') {
                addContextMenuItem('Konfigurieren...', () => openDeviceConfigModal(item));
                addContextMenuSeparator();
                addContextMenuItem('Löschen', () => { deleteDevice(item); hideContextMenu(); });
            } else if (type === 'connection') {
                addContextMenuItem('Verbindungstyp ändern...', () => openConnectionConfigModal(item));
                addContextMenuSeparator();
                addContextMenuItem('Löschen', () => { deleteConnection(item); hideContextMenu(); });
            }
        }

        function addContextMenuItem(label, action) {
            const item = document.createElement('div');
            item.className = 'context-menu-item';
            item.textContent = label;
            item.addEventListener('click', () => {
                action();
                hideContextMenu(); 
            });
            contextMenuElement.appendChild(item);
        }
        function addContextMenuSeparator() {
            const separator = document.createElement('div');
            separator.className = 'context-menu-separator';
            contextMenuElement.appendChild(separator);
        }

        function hideContextMenu() {
            contextMenuElement.style.display = 'none';
        }
        document.addEventListener('click', (event) => {
            if (!contextMenuElement.contains(event.target) && event.target !== canvas) {
                 if(contextMenuElement.style.display === 'block'){
                    hideContextMenu();
                 }
            }
        });
         document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideContextMenu();
                hideDeviceConfigModal();
                hideConnectionConfigModal();
            }
            if (event.ctrlKey && event.key === 'z') {
                event.preventDefault();
                undo();
            }
            if (event.ctrlKey && event.key === 'y') {
                event.preventDefault();
                redo();
            }
        });


        // --- Modals für Konfiguration ---
        function openDeviceConfigModal(device) {
            currentConfiguringDevice = device;
            document.getElementById('deviceConfigTitle').textContent = `Konfig: ${device.hostname || deviceProperties[device.type].label + '-' + device.id}`;
            document.getElementById('deviceHostname').value = device.hostname || `${deviceProperties[device.type].label}-${device.id}`;
            document.getElementById('deviceIpAddress').value = device.ipAddress || deviceProperties[device.type].defaultIpPrefix + device.id;
            deviceConfigModal.classList.add('active');
        }
        function hideDeviceConfigModal() {
            deviceConfigModal.classList.remove('active');
            currentConfiguringDevice = null;
        }
        document.getElementById('saveDeviceConfig').addEventListener('click', () => {
            if (currentConfiguringDevice) {
                saveState(); 
                currentConfiguringDevice.hostname = document.getElementById('deviceHostname').value.trim();
                currentConfiguringDevice.ipAddress = document.getElementById('deviceIpAddress').value.trim();
                redrawCanvas();
            }
            hideDeviceConfigModal();
        });
        document.getElementById('cancelDeviceConfig').addEventListener('click', hideDeviceConfigModal);

        function openConnectionConfigModal(connection) {
            currentConfiguringConnection = connection;
            document.getElementById('connectionConfigTitle').textContent = `Verbindungstyp ändern`;
            document.getElementById('connectionTypeSelect').value = connection.type || 'ethernet';
            connectionConfigModal.classList.add('active');
        }
        function hideConnectionConfigModal() {
            connectionConfigModal.classList.remove('active');
            currentConfiguringConnection = null;
        }
        document.getElementById('saveConnectionConfig').addEventListener('click', () => {
            if (currentConfiguringConnection) {
                saveState(); 
                currentConfiguringConnection.type = document.getElementById('connectionTypeSelect').value;
                redrawCanvas();
            }
            hideConnectionConfigModal();
        });
        document.getElementById('cancelConnectionConfig').addEventListener('click', hideConnectionConfigModal);


        // --- Kernfunktionen (Hinzufügen, Löschen, Verbinden, Paket) ---
        function addDevice(x, y, type) {
            saveState(); 
            const deviceData = deviceProperties[type];
            if (!deviceData) return;
            const newDevice = {
                id: nextDeviceId,
                type: type,
                x: x, y: y,
                icon: deviceData.icon,
                width:0, height:0, 
                hostname: `${deviceData.label}-${nextDeviceId}`,
                ipAddress: `${deviceData.defaultIpPrefix}${nextDeviceId}`,
                connections: 0 
            };
            nextDeviceId++;
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
                        
                        const firstDeviceProps = deviceProperties[firstDeviceForConnection.type];
                        const clickedDeviceProps = deviceProperties[clickedDevice.type];
                        const firstDeviceConnections = getDeviceConnectionCount(firstDeviceForConnection.id);
                        const clickedDeviceConnections = getDeviceConnectionCount(clickedDevice.id);

                        if (firstDeviceConnections >= firstDeviceProps.maxPorts) {
                            showMessage(`${firstDeviceProps.label} ${firstDeviceForConnection.hostname || `Gerät ${firstDeviceForConnection.id}`} hat bereits die maximale Anzahl von ${firstDeviceProps.maxPorts} Verbindungen.`, 3000);
                            firstDeviceForConnection = null;
                            redrawCanvas(); return;
                        }
                        if (clickedDeviceConnections >= clickedDeviceProps.maxPorts) {
                            showMessage(`${clickedDeviceProps.label} ${clickedDevice.hostname || `Gerät ${clickedDevice.id}`} hat bereits die maximale Anzahl von ${clickedDeviceProps.maxPorts} Verbindungen.`, 3000);
                            firstDeviceForConnection = null; 
                            redrawCanvas(); return;
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
                deleteDevice(clickedDevice);
            } else {
                const clickedConnection = getConnectionAt(x, y);
                if (clickedConnection) {
                    deleteConnection(clickedConnection);
                }
            }
        }

        function deleteDevice(deviceToDelete) {
            saveState(); 
            devices = devices.filter(d => d.id !== deviceToDelete.id);
            connections = connections.filter(conn => conn.fromDeviceId !== deviceToDelete.id && conn.toDeviceId !== deviceToDelete.id);
            packets = packets.filter(p => !p.path.includes(deviceToDelete.id));
            showMessage(`${deviceProperties[deviceToDelete.type].label} ${deviceToDelete.hostname || `Gerät ${deviceToDelete.id}`} gelöscht.`, 2000);
            redrawCanvas();
        }

        function deleteConnection(connectionToDelete) {
            saveState(); 
            connections = connections.filter(conn =>
                !( (conn.fromDeviceId === connectionToDelete.fromDeviceId && conn.toDeviceId === connectionToDelete.toDeviceId) ||
                   (conn.fromDeviceId === connectionToDelete.toDeviceId && conn.toDeviceId === connectionToDelete.fromDeviceId) )
            );
            packets = packets.filter(packet => {
                const sourceId = packet.path[packet.pathIndex];
                const nextHopId = packet.path[packet.pathIndex + 1];
                return !((sourceId === connectionToDelete.fromDeviceId && nextHopId === connectionToDelete.toDeviceId) ||
                         (sourceId === connectionToDelete.toDeviceId && nextHopId === connectionToDelete.fromDeviceId));
            });
            const fromDev = devices.find(d => d.id === connectionToDelete.fromDeviceId);
            const toDev = devices.find(d => d.id === connectionToDelete.toDeviceId);
            showMessage(`Verbindung zwischen ${fromDev?.hostname || `Gerät ${fromDev?.id}`} und ${toDev?.hostname || `Gerät ${toDev?.id}`} gelöscht.`, 2000);
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
                        redrawCanvas(); return;
                    }
                    const pathDeviceIds = findPath(packetSourceDevice, clickedDevice, devices, connections);
                    if(pathDeviceIds && pathDeviceIds.length > 0){
                        createPacket(packetSourceDevice, clickedDevice, pathDeviceIds);
                    } else {
                       showMessage(`Kein gültiger Pfad von ${packetSourceDevice.hostname || `Gerät ${packetSourceDevice.id}`} zu ${clickedDevice.hostname || `Gerät ${clickedDevice.id}`} gefunden.`);
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
                if (distanceSquared <= (packetSize * packetSize * 2 * 2) ) { 
                    return packet;
                }
            }
            return null;
        }
        
        function getConnectionAt(clickX, clickY) {
            for (const conn of connections) {
                const fromDevice = devices.find(d => d.id === conn.fromDeviceId);
                const toDevice = devices.find(d => d.id === conn.toDeviceId);
                if (!fromDevice || !toDevice) continue;

                const x1 = fromDevice.x, y1 = fromDevice.y, x2 = toDevice.x, y2 = toDevice.y;
                const lenSq = (x2 - x1) * (x2 - x1) + (y2 - y1) * (y2 - y1);
                if (lenSq === 0) continue; 

                let t = ((clickX - x1) * (x2 - x1) + (clickY - y1) * (y2 - y1)) / lenSq;
                t = Math.max(0, Math.min(1, t)); 

                const closestX = x1 + t * (x2 - x1);
                const closestY = y1 + t * (y2 - y1);
                const distSq = (clickX - closestX) * (clickX - closestX) + (clickY - closestY) * (clickY - closestY);

                if (Math.sqrt(distSq) < connectionClickThreshold) return conn;
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
            saveState(); 
            connections.push({ fromDeviceId: fromId, toDeviceId: toId, type: 'ethernet' }); 
            redrawCanvas();
        }

        // --- Pfadfindung (BFS) ---
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
                if (currentDeviceId === endDevice.id) return currentPathArray; 

                let canExploreFromCurrent = deviceProperties[currentDevice.type].isForwarder || currentDevice.id === startDevice.id;
                if (canExploreFromCurrent) {
                    const neighbors = getNeighbors(currentDeviceId, allDevices, allConnections);
                    for (const neighborDevice of neighbors) {
                        if (!visitedPaths.has(neighborDevice.id)) { 
                            let canHopToNeighbor = true; 
                            if (currentDevice.type === 'internet' && neighborDevice.type !== 'router') canHopToNeighbor = false;
                            if (neighborDevice.type === 'internet' && currentDevice.type !== 'router') canHopToNeighbor = false;
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
            const sourceIp = initialSourceDevice.ipAddress || `${deviceProperties[initialSourceDevice.type].defaultIpPrefix}${initialSourceDevice.id}`;
            let destinationIp = finalDestinationDevice.ipAddress || `${deviceProperties[finalDestinationDevice.type].defaultIpPrefix}${finalDestinationDevice.id}`;
            if (finalDestinationDevice.type === 'internet') destinationIp = `8.8.8.8`; 

            const protocol = 'SIM_ICMP';
            const packetInfoText = `P${nextPacketId}: ${sourceIp} -> ${destinationIp} (${protocol})`;

            const newPacket = {
                id: nextPacketId++,
                path: pathDeviceIds, pathIndex: 0,
                finalDestinationDeviceId: finalDestinationDevice.id,
                x: initialSourceDevice.x, y: initialSourceDevice.y,
                progress: 0, speed: packetSpeed,
                color: `hsl(${Math.random() * 360}, 100%, 50%)`,
                status: 'traveling', processingTimer: 0,
                sourceIp: sourceIp, destinationIp: destinationIp,
                protocol: protocol, packetInfo: packetInfoText
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
                    packets.splice(i, 1); continue;
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
                            showMessage(`Paket ${packet.id} hat ${currentSegmentNextHopDevice.hostname || `Gerät ${currentSegmentNextHopDevice.id}`} erreicht!`, 2000);
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

        // --- Animationsschleife ---
        function startAnimationLoop() {
            if (!animationFrameId) animatePackets();
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
            if (packets.length > 0 || isConnecting || isSendingPacketMode || isDeletingMode || selectedDeviceType || draggingDevice) {
                animationFrameId = requestAnimationFrame(animatePackets);
            } else {
                animationFrameId = null;
            }
        }

        // --- Initialisierung ---
        resizeCanvas();
        updateToolbarButtons(); 
        console.log("Netzwerk-Diagrammersteller V3 initialisiert.");
    </script>
</body>
</html>
