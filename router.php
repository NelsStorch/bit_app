<!--
  Datei: router.php
  Beschreibung: Ein interaktives Lernspiel, bei dem Spieler IP-Pakete an die korrekten Ports
  oder das Gateway leiten müssen. Es verwendet Canvas-Animationen und Soundeffekte.

  Technologien: HTML5, CSS3, JavaScript, Tone.js (für Audio), Tailwind CSS
-->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Router Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.49/Tone.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Dark Mode Theme Variablen */
            --bg-gradient-start: #1a1c20;
            --bg-gradient-end: #2c3e50;
            --container-bg: #2d3436;
            --component-bg: #1e272e;
            --component-light-bg: #34495e;
            --border-color: #4b6584;
            --text-light: #ecf0f1;
            --text-medium: #bdc3c7;
            --text-dark: #2c3e50;

            --primary-accent: #3498db;
            --primary-accent-dark: #2980b9;
            --success-green: #2ecc71;
            --warning-orange: #f39c12;
            --danger-red: #e74c3c;
            --info-purple: #9b59b6;
            --white: #ffffff;

            --led-off: #34495e;
            --led-green: #2ecc71;
            --led-activity: #f1c40f;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden; /* Prevent scrolling of body */
        }

        #game-container {
            background-color: var(--container-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 98%;
            max-width: 1400px;
            height: 95vh;
            display: flex;
            gap: 20px;
            position: relative;
            border: 1px solid #444;
            overflow: hidden;
        }

        /* --- Linker Bereich: Router Rack --- */
        #router-area {
            flex: 2;
            background-color: #000; /* Rack interior */
            border-radius: 8px;
            padding: 15px;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 4px solid #2d3436;
            border-left: 4px solid #555; /* 3D effect */
            border-right: 4px solid #111;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.8);
            overflow-y: auto;
        }

        /* Rack Mount Device Look */
        .rack-device {
            background: linear-gradient(to bottom, #3a4042, #2c3133);
            border: 1px solid #1a1c1e;
            border-radius: 4px;
            margin-bottom: 20px;
            padding: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
        }

        /* Schrauben für Rack-Optik */
        .rack-device::before, .rack-device::after {
            content: ''; position: absolute; top: 50%; width: 8px; height: 8px;
            background: radial-gradient(circle, #aaa 20%, #555 100%);
            border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.5);
            transform: translateY(-50%);
        }
        .rack-device::before { left: 8px; }
        .rack-device::after { right: 8px; }

        .device-label {
            position: absolute; top: 5px; left: 25px;
            font-family: 'Courier New', monospace; font-weight: bold; font-size: 0.8em;
            color: rgba(255,255,255,0.7); letter-spacing: 1px;
            text-shadow: 0 1px 1px rgba(0,0,0,1);
        }

        #ports-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start; /* Linksbündig für Switch-Look */
            padding: 25px 30px 10px 30px; /* Platz für Rack-Ohren/Schrauben */
        }

        /* Port Styling */
        .port-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background-color: #222;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #444;
        }

        .port {
            width: 42px; height: 42px;
            background-color: #111;
            border: 2px solid #555;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
            transition: all 0.15s ease;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.8);
        }

        /* RJ45 Socket Look */
        .port::after {
            content: ''; width: 28px; height: 20px;
            background-color: #000;
            border-top: 1px solid #333;
            border-radius: 2px 2px 0 0;
        }
        /* Gold Pins */
        .port::before {
            content: ''; position: absolute; bottom: 12px;
            width: 16px; height: 4px;
            background: repeating-linear-gradient(90deg, #d4af37, #d4af37 2px, transparent 2px, transparent 4px);
            opacity: 0.6;
            z-index: 1;
        }

        .port:hover {
            border-color: var(--primary-accent);
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.4);
        }

        .port.gateway {
            border-color: var(--info-purple);
        }
        .port.gateway::after { background-color: #1a0f1a; }

        /* LEDs */
        .led-status {
            position: absolute; top: 4px; left: 4px;
            width: 6px; height: 6px; border-radius: 50%;
            background-color: var(--led-off);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
        }
        .led-act {
            position: absolute; top: 4px; right: 4px;
            width: 6px; height: 6px; border-radius: 50%;
            background-color: var(--led-off);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
        }

        /* Port Nummer */
        .port-number-label {
            font-size: 0.6em; color: #888;
            margin-top: 2px; text-align: center;
            width: 100%; font-family: monospace;
        }

        /* Active States */
        .port.active .led-status { background-color: var(--led-green); box-shadow: 0 0 4px var(--led-green); }
        .port.activity .led-act { background-color: var(--led-activity); box-shadow: 0 0 4px var(--led-activity); animation: blink-fast 0.2s infinite; }

        .port.correct { border-color: var(--success-green); box-shadow: 0 0 15px var(--success-green); }
        .port.incorrect { border-color: var(--danger-red); box-shadow: 0 0 15px var(--danger-red); }

        @keyframes blink-fast { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        /* --- Incoming Packet Area --- */
        #incoming-packet-area {
            margin-top: auto;
            background: linear-gradient(to right, var(--component-bg), var(--component-light-bg));
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: sticky; bottom: 0; z-index: 10;
        }

        #incoming-packet-info {
            flex-grow: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .packet-display-box {
            background-color: rgba(0,0,0,0.3);
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid var(--primary-accent);
            font-family: monospace;
            font-size: 1.1em;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 250px;
        }
        .packet-display-box.bad { border-color: var(--danger-red); }
        .packet-display-box.external { border-color: var(--info-purple); }

        /* --- Rechter Bereich: Info & Tools --- */
        #info-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-y: auto;
            max-width: 400px;
        }

        .panel {
            background-color: var(--component-bg);
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        h3 {
            margin: 0 0 12px 0;
            color: var(--primary-accent);
            font-size: 1.1em;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Routing Table */
        #ip-table-wrapper {
            max-height: 250px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-accent) var(--component-bg);
        }
        #ip-table { width: 100%; border-collapse: collapse; font-size: 0.85em; }
        #ip-table th { text-align: left; padding: 8px; background-color: rgba(0,0,0,0.2); position: sticky; top: 0; }
        #ip-table td { padding: 6px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); font-family: monospace; }
        #ip-table tr:hover { background-color: rgba(255,255,255,0.05); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .stat-item {
            background-color: rgba(0,0,0,0.2);
            padding: 8px;
            border-radius: 4px;
            text-align: center;
        }
        .stat-value { font-size: 1.2em; font-weight: bold; display: block; }
        .stat-label { font-size: 0.75em; color: var(--text-medium); }

        /* Action Button */
        #block-button {
            width: 100%;
            padding: 15px;
            background-color: var(--danger-red);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s, background-color 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        #block-button:hover:not(:disabled) { background-color: #c0392b; transform: translateY(-2px); }
        #block-button:active:not(:disabled) { transform: translateY(0); }
        #block-button:disabled { background-color: #555; cursor: not-allowed; opacity: 0.6; }

        /* Timer */
        #timer-container { width: 100%; margin-top: 10px; }
        #timer-bar-bg { height: 8px; background-color: rgba(0,0,0,0.3); border-radius: 4px; overflow: hidden; }
        #timer-bar { height: 100%; width: 100%; background-color: var(--success-green); transition: width 0.1s linear; }

        /* Overlays */
        #overlay-screen {
            position: absolute; inset: 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 100;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            transition: opacity 0.3s;
        }
        #overlay-screen.hidden { opacity: 0; pointer-events: none; }

        .btn-primary {
            background-color: var(--primary-accent);
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 1.2em;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.2s;
        }
        .btn-primary:hover { background-color: var(--primary-accent-dark); }

        /* Animated Packet */
        #animated-packet {
            position: absolute;
            width: 20px; height: 20px;
            background-color: var(--white);
            border-radius: 50%;
            box-shadow: 0 0 10px white;
            pointer-events: none;
            opacity: 0;
            z-index: 50;
        }
        #animated-packet.visible { opacity: 1; transition: top 0.5s ease-in-out, left 0.5s ease-in-out; }
    </style>
</head>
<body>
    <a href="index.html" class="absolute top-4 left-4 text-white hover:text-gray-300 z-50 flex items-center gap-2 bg-gray-800 px-3 py-2 rounded-lg opacity-80 hover:opacity-100 transition-opacity no-underline shadow-lg border border-gray-600" title="Zur Startseite">
        <i class="fas fa-home"></i>
        <span class="font-medium">Home</span>
    </a>

    <div id="game-container">
        <!-- Center Rack / Router Area -->
        <div id="router-area">
            <div class="rack-device">
                <div class="device-label">CORE-ROUTER-X48</div>
                <div id="ports-container">
                    <!-- Ports werden hier dynamisch eingefügt -->
                </div>
            </div>

            <div id="incoming-packet-area">
                <div id="incoming-packet-info">
                    <div style="font-size: 2em; margin-right: 15px;">📦</div>
                    <div id="packet-details" class="packet-display-box">
                        <span class="text-gray-400 italic">Warte auf Paket...</span>
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <div id="timer-text" class="text-right text-sm font-mono mb-1">-- s</div>
                    <div id="timer-container">
                        <div id="timer-bar-bg"><div id="timer-bar"></div></div>
                    </div>
                </div>
            </div>

            <div id="animated-packet"></div>
        </div>

        <!-- Right Side: Info Panel -->
        <div id="info-area">
            <div class="panel">
                <h3><i class="fas fa-chart-line"></i> Status</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value" id="score-display">0</span>
                        <span class="stat-label">Punkte</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" id="level-display">1</span>
                        <span class="stat-label">Level</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value text-red-400" id="error-display">0</span>
                        <span class="stat-label">Fehler</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value text-yellow-400" id="combo-display">x1</span>
                        <span class="stat-label">Combo</span>
                    </div>
                </div>
                <div class="mt-2 text-center text-xs text-gray-500">Highscore: <span id="highscore-display">0</span></div>
            </div>

            <div class="panel" style="flex-grow: 1; display: flex; flex-direction: column;">
                <h3><i class="fas fa-network-wired"></i> Routing Tabelle</h3>
                <div id="ip-table-wrapper">
                    <table id="ip-table">
                        <thead><tr><th>Netzwerk</th><th>Port</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <button id="block-button" disabled>
                <i class="fas fa-ban"></i> BLOCKIEREN
            </button>
        </div>
    </div>

    <!-- Overlays -->
    <div id="overlay-screen">
        <h1 class="text-5xl font-bold mb-4 text-blue-400">IP Router Master</h1>
        <p class="text-xl mb-8 max-w-lg">Leite Pakete an den richtigen Port. Blockiere fehlerhafte oder bösartige Pakete.</p>

        <div id="start-content">
            <input type="text" id="player-name-input" placeholder="Spielername" class="p-3 rounded bg-gray-700 text-white text-center border border-gray-500 mb-4 w-64 block mx-auto focus:outline-none focus:border-blue-400">
            <button id="start-btn" class="btn-primary">Spiel Starten</button>

            <div class="mt-10 bg-gray-800 p-4 rounded-lg max-w-md mx-auto text-left">
                <h4 class="text-lg font-bold border-b border-gray-600 pb-2 mb-2">🏆 Highscores</h4>
                <ul id="highscore-list" class="space-y-1 text-sm text-gray-300 h-32 overflow-y-auto">
                    <li>Lade...</li>
                </ul>
            </div>
        </div>

        <div id="game-over-content" class="hidden">
            <h2 class="text-4xl text-red-500 font-bold mb-2">Game Over</h2>
            <p class="text-2xl mb-6">Erreichte Punkte: <span id="final-score" class="text-white font-mono">0</span></p>
            <button id="restart-btn" class="btn-primary">Neustart</button>
        </div>
    </div>

    <script>
        /**
         * Router Spiel Logik
         * Verwaltet Spielzustand, Port-Generierung und Paket-Routing.
         */

        // --- Konfiguration ---
        const CONFIG = {
            gatewayPort: 0,
            maxCombo: 8,
            basePacketInterval: 4000,
            baseDecisionTime: 10000,
            portsPerRow: 12 // Für das Rack-Layout
        };

        // --- DOM Elemente ---
        const ui = {
            portsContainer: document.getElementById('ports-container'),
            packetDetails: document.getElementById('packet-details'),
            timerBar: document.getElementById('timer-bar'),
            timerText: document.getElementById('timer-text'),
            score: document.getElementById('score-display'),
            level: document.getElementById('level-display'),
            errors: document.getElementById('error-display'),
            combo: document.getElementById('combo-display'),
            highscore: document.getElementById('highscore-display'),
            ipTableBody: document.querySelector('#ip-table tbody'),
            blockBtn: document.getElementById('block-button'),
            overlay: document.getElementById('overlay-screen'),
            startContent: document.getElementById('start-content'),
            gameOverContent: document.getElementById('game-over-content'),
            finalScore: document.getElementById('final-score'),
            animatedPacket: document.getElementById('animated-packet'),
            highscoreList: document.getElementById('highscore-list'),
            nameInput: document.getElementById('player-name-input')
        };

        // --- Spielzustand ---
        let state = {
            active: false,
            score: 0,
            highscore: 0,
            level: 1,
            errors: 0,
            combo: 1,
            portCount: 4,
            ipTable: new Map(), // Netzwerk -> Port ID
            currentPacket: null,
            timerInterval: null,
            packetTimeout: null,
            timeLeft: 0,
            decisionTime: CONFIG.baseDecisionTime,
            packetInterval: CONFIG.basePacketInterval,
            blockedIPs: new Set(),
            nextSubnet: 1,
            audioContextStarted: false
        };

        // --- Audio (Tone.js) ---
        const audio = {
            synth: new Tone.Synth().toDestination(),
            metal: new Tone.MetalSynth({ harmonicity: 12, resonance: 800, modulationIndex: 20, envelope: { decay: 0.4, } }).toDestination(),
            membrane: new Tone.MembraneSynth().toDestination(),

            play(type) {
                // Ensure context is running, otherwise ignore (or it will throw warnings)
                if (Tone.context.state !== 'running') return;

                // Add a safe buffer for scheduling.
                // "Start time must be strictly greater than previous start time" often happens
                // if we schedule too close to the current time processing block.
                const now = Tone.now() + 0.15;

                try {
                    switch (type) {
                        case 'packet': this.synth.triggerAttackRelease("C5", "32n", now); break;
                        case 'success': this.synth.triggerAttackRelease("G5", "16n", now); break;
                        case 'gateway': this.synth.triggerAttackRelease("E5", "16n", now); break;
                        case 'block': this.metal.triggerAttackRelease("32n", now, 0.6); break;
                        case 'error': this.membrane.triggerAttackRelease("C2", "8n", now); break;
                        case 'levelup':
                            // Schedule sequence
                            this.synth.triggerAttackRelease("C4", "16n", now);
                            this.synth.triggerAttackRelease("E4", "16n", now + 0.2);
                            this.synth.triggerAttackRelease("G4", "16n", now + 0.4);
                            break;
                    }
                } catch(e) {
                    console.warn("Audio play error:", e);
                }
            }
        };

        // --- Initialisierung ---
        function init() {
            const storedHighscore = localStorage.getItem('routerGameHighscore');
            if (storedHighscore) state.highscore = parseInt(storedHighscore);
            ui.highscore.textContent = state.highscore;

            document.getElementById('start-btn').addEventListener('click', startGame);
            document.getElementById('restart-btn').addEventListener('click', () => {
                ui.gameOverContent.classList.add('hidden');
                ui.startContent.classList.remove('hidden');
                startGame();
            });

            ui.blockBtn.addEventListener('click', handleBlock);

            fetchHighscores();
        }

        // --- Core Game Functions ---

        async function startGame() {
            const name = ui.nameInput.value.trim();
            if(!name) { alert("Bitte Namen eingeben!"); return; }

            // Initialize Audio Context on user gesture
            if (Tone.context.state !== 'running') {
                try {
                    await Tone.start();
                } catch(e) {
                    console.error("Could not start audio context:", e);
                }
            }

            state.active = true;
            state.score = 0;
            state.level = 1;
            state.errors = 0;
            state.combo = 1;
            state.portCount = 4;
            state.ipTable.clear();
            state.blockedIPs.clear();
            state.nextSubnet = 1;
            state.decisionTime = CONFIG.baseDecisionTime;

            ui.overlay.classList.add('hidden');
            ui.gameOverContent.classList.add('hidden');

            updateStatsUI();

            // Initial Networks
            for(let i=1; i<=state.portCount; i++) addNetworkRoute(i);

            createPortsUI();
            updateRoutingTableUI();
            nextPacket();
        }

        function gameOver() {
            state.active = false;
            clearTimeout(state.packetTimeout);
            clearInterval(state.timerInterval);

            ui.finalScore.textContent = state.score;
            ui.startContent.classList.add('hidden');
            ui.gameOverContent.classList.remove('hidden');
            ui.overlay.classList.remove('hidden');

            saveHighscore(ui.nameInput.value, state.score);
            if(state.score > state.highscore) {
                state.highscore = state.score;
                localStorage.setItem('routerGameHighscore', state.highscore);
            }
        }

        // --- Port Logic ---

        /**
         * Erstellt das UI für die Ports im "Rack"-Design.
         * Gruppiert Ports visuell, um hohe Portdichten darzustellen.
         */
        function createPortsUI() {
            ui.portsContainer.innerHTML = '';

            // Gateway Port (Speziell)
            const gwWrapper = document.createElement('div');
            gwWrapper.className = 'port-group';
            const gwPort = createSinglePortUI(CONFIG.gatewayPort, true);
            gwWrapper.appendChild(gwPort);
            gwWrapper.appendChild(createLabel('WAN/GW'));
            ui.portsContainer.appendChild(gwWrapper);

            // Lokale Ports
            // Wir erstellen Blöcke zu je 2 Ports übereinander, wenn viele Ports da sind
            // Oder einfache Reihen. Für Switch-Look: Gruppen von 2/4/8.

            // Logik: Wir machen immer 2er Spalten (oben/unten) wie bei echten Switches
            // Wenn count=4: 2 Spalten à 2 Ports.
            const totalLocals = state.portCount;

            for (let i = 1; i <= totalLocals; i++) {
                // Wir packen jeden Port in einen Wrapper, oder gruppieren sie?
                // Simpler Ansatz für Responsiveness: Flex-Wrap mit einzelnen Ports,
                // aber visuell eng gepackt.

                const pWrapper = document.createElement('div');
                pWrapper.className = 'port-group';

                const pUI = createSinglePortUI(i, false);
                pWrapper.appendChild(pUI);
                pWrapper.appendChild(createLabel(i));

                ui.portsContainer.appendChild(pWrapper);
            }
        }

        function createSinglePortUI(id, isGateway) {
            const p = document.createElement('div');
            p.className = `port ${isGateway ? 'gateway' : ''}`;
            p.dataset.id = id;
            p.innerHTML = `<div class="led-status"></div><div class="led-act"></div>`;
            p.title = isGateway ? "Gateway (Internet)" : `Port ${id}`;

            p.addEventListener('click', () => handlePortClick(id, p));
            return p;
        }

        function createLabel(text) {
            const l = document.createElement('div');
            l.className = 'port-number-label';
            l.textContent = text;
            return l;
        }

        function addNetworkRoute(portId) {
            const subnet = `192.168.${state.nextSubnet}`;
            state.ipTable.set(subnet, portId);
            state.nextSubnet++;
        }

        // --- Packet Generation & Handling ---

        function nextPacket() {
            if(!state.active) return;

            ui.blockBtn.disabled = true;
            ui.packetDetails.innerHTML = '<span class="text-gray-400">Warte...</span>';
            ui.packetDetails.className = 'packet-display-box';

            const delay = Math.max(1000, 3000 - (state.level * 100));

            state.packetTimeout = setTimeout(() => {
                generatePacket();
            }, delay);
        }

        function generatePacket() {
            audio.play('packet');

            // Zufälliger Quell-Port (Simuliert eingehend)
            const incomingPort = Math.floor(Math.random() * state.portCount) + 1;

            // Generiere IPs
            const srcIP = generateRandomIP();

            // Entscheide Typ: Normal, External, Bad
            const rand = Math.random();
            let type = 'normal';
            let destIP;
            let targetPort;

            // 20% Chance auf Bad Packet (Spoofed IP, Blocked IP etc)
            // Für Vereinfachung: Bad Packet = IP von Blocklist oder Malformed (hier simuliert durch Zufall)
            // Wir nutzen state.blockedIPs für "bekannte böse IPs"
            let isBad = false;

            if (rand < 0.15 + (state.level * 0.02)) {
                // Bad Packet
                type = 'bad';
                isBad = true;
                destIP = generateRandomIP(); // Irgendwohin
                // In höheren Levels könnte hier Logik für "Spoofing" rein
            } else if (rand < 0.4) {
                // External (Internet)
                type = 'external';
                destIP = `${Math.floor(Math.random()*200+1)}.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}`;
                targetPort = CONFIG.gatewayPort;
            } else {
                // Internal (Local)
                type = 'local';
                // Wähle ein existierendes Subnetz
                const keys = Array.from(state.ipTable.keys());
                const targetSubnet = keys[Math.floor(Math.random() * keys.length)];
                destIP = `${targetSubnet}.${Math.floor(Math.random()*253)+1}`;
                targetPort = state.ipTable.get(targetSubnet);
            }

            state.currentPacket = {
                src: srcIP,
                dest: destIP,
                type: type,
                targetPort: targetPort,
                isBad: isBad
            };

            displayPacket(state.currentPacket);
            startTimer();
            ui.blockBtn.disabled = false;
        }

        function generateRandomIP() {
            return `10.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}`;
        }

        function displayPacket(pkt) {
            let typeHtml = '';
            let cssClass = '';

            if (pkt.isBad) {
                typeHtml = '<span class="text-red-400 font-bold">[SUSPICIOUS]</span>';
                cssClass = 'bad';
            } else if (pkt.type === 'external') {
                typeHtml = '<span class="text-purple-400">➔ WAN</span>';
                cssClass = 'external';
            } else {
                typeHtml = '<span class="text-blue-400">➔ LAN</span>';
            }

            ui.packetDetails.className = `packet-display-box ${cssClass}`;
            ui.packetDetails.innerHTML = `
                <div class="text-xs text-gray-400">SRC: ${pkt.src} (Port ${Math.floor(Math.random()*state.portCount)+1})</div>
                <div class="text-lg font-bold text-white">DST: ${pkt.dest}</div>
                <div class="text-xs text-right">${typeHtml}</div>
            `;

            // Visual feedback on random port LED
            const randomPort = document.querySelector(`.port[data-id="${Math.floor(Math.random()*state.portCount)+1}"]`);
            if(randomPort) {
                randomPort.classList.add('activity');
                setTimeout(() => randomPort.classList.remove('activity'), 200);
            }
        }

        // --- Interaction Handlers ---

        function handlePortClick(portId, portElement) {
            if(!state.currentPacket || !state.active) return;

            clearInterval(state.timerInterval);

            const pkt = state.currentPacket;
            let success = false;

            if (pkt.isBad) {
                // Fehler: Hätte blockiert werden müssen
                success = false;
            } else {
                if (pkt.targetPort === portId) success = true;
            }

            resolveRound(success, portElement);
        }

        function handleBlock() {
            if(!state.currentPacket || !state.active) return;
            clearInterval(state.timerInterval);

            const pkt = state.currentPacket;
            // Success wenn Paket wirklich Bad war
            const success = pkt.isBad;

            resolveRound(success, null, true);
        }

        function resolveRound(success, targetEl, isBlockAction = false) {
            state.currentPacket = null;
            ui.blockBtn.disabled = true;

            if (success) {
                // Correct
                audio.play(isBlockAction ? 'block' : (targetEl && targetEl.dataset.id == 0 ? 'gateway' : 'success'));

                const points = 100 * state.combo;
                state.score += points;
                state.combo = Math.min(state.combo + 1, CONFIG.maxCombo);

                if (targetEl) {
                    targetEl.classList.add('correct');
                    targetEl.classList.add('active'); // LED green
                    animatePacket(targetEl);
                }

                checkLevelUp();
            } else {
                // Error
                audio.play('error');
                state.errors++;
                state.combo = 1;
                state.score = Math.max(0, state.score - 50);

                ui.gameContainer?.classList.add('shake'); // Shake effect (CSS needed?)
                if(targetEl) targetEl.classList.add('incorrect');

                if(state.errors >= 5) {
                    gameOver();
                    return;
                }
            }

            updateStatsUI();

            // Cleanup visuals after delay
            setTimeout(() => {
                document.querySelectorAll('.port').forEach(p => p.classList.remove('correct', 'incorrect', 'active'));
                nextPacket();
            }, 800);
        }

        function animatePacket(targetEl) {
            const rectStart = ui.packetDetails.getBoundingClientRect();
            const rectEnd = targetEl.getBoundingClientRect();

            ui.animatedPacket.style.left = rectStart.left + 'px';
            ui.animatedPacket.style.top = rectStart.top + 'px';
            ui.animatedPacket.classList.add('visible');

            // Force reflow
            void ui.animatedPacket.offsetWidth;

            ui.animatedPacket.style.left = (rectEnd.left + 10) + 'px';
            ui.animatedPacket.style.top = (rectEnd.top + 10) + 'px';

            setTimeout(() => {
                ui.animatedPacket.classList.remove('visible');
            }, 500);
        }

        // --- Timer Logic ---
        function startTimer() {
            state.timeLeft = state.decisionTime;
            updateTimerUI();

            clearInterval(state.timerInterval);
            state.timerInterval = setInterval(() => {
                state.timeLeft -= 50;
                updateTimerUI();

                if(state.timeLeft <= 0) {
                    clearInterval(state.timerInterval);
                    // Time run out -> Count as error? Or just miss?
                    // Let's count as error.
                    audio.play('error');
                    state.errors++;
                    state.combo = 1;
                    updateStatsUI();
                    if(state.errors >= 5) gameOver();
                    else nextPacket();
                }
            }, 50);
        }

        function updateTimerUI() {
            const pct = (state.timeLeft / state.decisionTime) * 100;
            ui.timerBar.style.width = pct + '%';
            ui.timerText.textContent = (state.timeLeft / 1000).toFixed(1) + ' s';

            if(pct < 30) ui.timerBar.style.backgroundColor = '#e74c3c';
            else if(pct < 60) ui.timerBar.style.backgroundColor = '#f1c40f';
            else ui.timerBar.style.backgroundColor = '#2ecc71';
        }

        // --- Level & Progess ---
        function checkLevelUp() {
            const threshold = state.level * 800;
            if (state.score > threshold) {
                state.level++;
                audio.play('levelup');

                // Increase difficulty
                state.decisionTime = Math.max(2000, state.decisionTime - 500);

                // Add ports occasionally
                if (state.level % 2 === 0) {
                    state.portCount += 4; // Add block of 4
                    // Add routes for new ports
                    for(let i=state.portCount-3; i<=state.portCount; i++) {
                        addNetworkRoute(i);
                    }
                    createPortsUI();
                    updateRoutingTableUI();
                }
            }
        }

        function updateStatsUI() {
            ui.score.textContent = state.score;
            ui.level.textContent = state.level;
            ui.errors.textContent = state.errors;
            ui.combo.textContent = 'x' + state.combo;
        }

        function updateRoutingTableUI() {
            ui.ipTableBody.innerHTML = '';

            // Gateway
            const rowGw = ui.ipTableBody.insertRow();
            rowGw.insertCell(0).textContent = "0.0.0.0/0 (Internet)";
            rowGw.insertCell(1).innerHTML = '<span class="text-purple-400 font-bold">WAN</span>';

            // Locals
            state.ipTable.forEach((port, net) => {
                const row = ui.ipTableBody.insertRow();
                row.insertCell(0).textContent = net + '.0/24';
                row.insertCell(1).textContent = `Port ${port}`;
            });
        }

        // --- Highscore API ---
        async function fetchHighscores() {
            try {
                const res = await fetch('save_highscore.php');

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.warn("Highscore fetch failed (non-JSON):", text);
                    ui.highscoreList.innerHTML = '<li class="text-red-400">DB Error</li>';
                    return;
                }

                if (!res.ok || data.status === 'error') {
                     console.warn("Highscore API Error:", data.message || res.statusText);
                     return;
                }

                if (!Array.isArray(data)) return; // Should be array on success

                ui.highscoreList.innerHTML = '';
                if(data.length === 0) {
                    ui.highscoreList.innerHTML = '<li>Keine Einträge.</li>';
                    return;
                }

                data.forEach((entry, idx) => {
                    const li = document.createElement('li');
                    li.className = 'flex justify-between border-b border-gray-700 pb-1';
                    li.innerHTML = `<span>${idx+1}. ${escapeHtml(entry.player_name)}</span> <span class="text-blue-300">${entry.score}</span>`;
                    ui.highscoreList.appendChild(li);
                });
            } catch(e) { console.error(e); }
        }

        async function saveHighscore(name, score) {
            try {
                await fetch('save_highscore.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({player_name: name, score: score})
                });
                fetchHighscores();
            } catch(e) { console.error(e); }
        }

        function escapeHtml(text) {
            if (!text) return text;
            return text.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        }

        // Start init
        init();

    </script>
</body>
</html>
