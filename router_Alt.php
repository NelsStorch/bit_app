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
    <style>
        :root {
            /* Dark Mode Theme Variablen */
            --bg-gradient-start: #232526;
            --bg-gradient-end: #414345;
            --container-bg: #2d3436;
            --component-bg: #3b4446;
            --component-light-bg: #4a5459;
            --border-color: #555e61;
            --text-light: #dfe6e9;
            --text-medium: #b2bec3;
            --text-dark: #2d3436;

            --primary-accent: #0984e3;
            --primary-accent-dark: #005cb2;
            --success-green: #00b894;
            --warning-orange: #fdcb6e; /* Verwendet für Combo */
            --danger-red: #d63031;
            --info-purple: #a29bfe; /* Farbe für Gateway-Pakete */
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }

        #game-container {
            background-color: var(--container-bg);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.4);
            width: 95%;
            max-width: 1100px;
            display: flex;
            gap: 30px;
            position: relative;
            border: 1px solid #444;
        }

        #router-area {
            flex-basis: 60%;
            background-color: var(--component-bg);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            min-height: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid var(--border-color);
        }

        #info-area {
            flex-basis: 40%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        #router-visualization {
            width: 90%; height: 80px; background: linear-gradient(to bottom, #666, #444);
            border: 3px solid var(--primary-accent); border-radius: 10px; margin-bottom: 35px;
            display: flex; justify-content: center; align-items: center; color: var(--text-light);
            font-weight: bold; font-size: 1.4em; box-shadow: inset 0 0 15px rgba(0,0,0,0.7), 0 2px 5px rgba(0,0,0,0.3);
            letter-spacing: 3px; text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
        }
        #router-visualization::before, #router-visualization::after { /* Blinkende Lichter */
            background-color: var(--success-green); box-shadow: 0 0 6px var(--success-green);
             content: ''; position: absolute; width: 8px; height: 8px; border-radius: 50%;
             animation: blink 1.5s infinite alternate;
        }
         #router-visualization::before { top: 15px; left: 20px; animation-delay: 0s; }
         #router-visualization::after { top: 15px; right: 20px; animation-delay: 0.75s; }
         @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.2; } 100% { opacity: 1; } }

        #ports-container {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px;
            width: 100%; margin-bottom: 25px;
        }

        .port {
            background-color: var(--component-light-bg); border: 2px solid var(--border-color);
            color: var(--text-medium); border-radius: 8px; height: 60px; display: flex;
            flex-direction: column; justify-content: center; align-items: center;
            font-weight: bold; font-size: 0.9em; cursor: pointer; transition: all 0.25s ease;
            position: relative; padding: 5px; line-height: 1.2; box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }
        .port .port-number { font-size: 1.2em; color: var(--primary-accent); }
        .port .port-ip { font-size: 0.75em; font-weight: normal; color: var(--text-medium); }
        .port:hover {
            transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.3);
            border-color: var(--primary-accent); background-color: #5a6469;
        }
         .port:hover .port-ip { color: var(--text-light); }
        .port.gateway {
             background: linear-gradient(to bottom, var(--primary-accent), var(--primary-accent-dark));
             border-color: var(--primary-accent-dark); color: var(--white);
        }
         .port.gateway .port-number { color: var(--white); }
         .port.gateway .port-ip { color: #d0e0f0; }
         .port.gateway:hover { background: linear-gradient(to bottom, var(--primary-accent-dark), #003c7a); }
        .port.correct { animation: flash-green 0.7s ease; }
        .port.incorrect { animation: flash-red 0.7s ease; }
        @keyframes flash-green { /* ... */
             0%, 100% { transform: scale(1); }
             50% { background-color: var(--success-green); color: white; transform: scale(1.1); box-shadow: 0 0 15px var(--success-green); } }
        @keyframes flash-red { /* ... */
             0%, 100% { transform: scale(1); }
             50% { background-color: var(--danger-red); color: white; transform: scale(1.1); box-shadow: 0 0 15px var(--danger-red); } }
        @keyframes shake-error { /* ... */
            10%, 90% { transform: translate(-1px, -1px) rotate(-1deg); } 20%, 80% { transform: translate(2px, 1px) rotate(1deg); }
            30%, 50%, 70% { transform: translate(-3px, 2px) rotate(-2deg); } 40%, 60% { transform: translate(3px, -1px) rotate(2deg); } }

        #incoming-packet-area {
            margin-top: auto; background-color: var(--component-light-bg);
            border: 1px solid var(--border-color); padding: 20px;
            border-radius: 10px; width: 95%; text-align: center;
            box-shadow: 0 3px 7px rgba(0,0,0,0.1);
        }
        #incoming-packet {
            font-size: 0.95em; min-height: 90px; display: flex; flex-direction: column;
            justify-content: center; align-items: center; gap: 5px; color: var(--text-light);
        }
        .packet-icon { font-size: 1.8em; margin-bottom: 8px; }
        .packet-icon.bad { color: var(--danger-red); }
        .packet-icon.external { color: var(--info-purple); }

        #ip-table-container, #score-container {
            background-color: var(--component-bg); border: 1px solid var(--border-color);
            color: var(--text-light); padding: 20px; border-radius: 10px;
        }
        #ip-table-container h3, #score-container h3 {
            margin-top: 0; margin-bottom: 15px; color: var(--primary-accent);
            border-bottom: 2px solid var(--primary-accent); padding-bottom: 8px;
            font-weight: 600; font-size: 1.2em;
        }
        #ip-table { font-size: 0.85em; width: 100%; border-collapse: collapse; }
        #ip-table th, #ip-table td { text-align: left; padding: 6px 10px; border-bottom: 1px solid var(--border-color); }
        #ip-table th { background-color: var(--component-light-bg); color: var(--primary-accent); font-weight: 600; }
        #ip-table tr:last-child td { border-bottom: none; }

        #score-display, #highscore-display, #error-display, #level-display { font-size: 1.1em; margin-bottom: 8px; }
        #error-display span { color: var(--danger-red); font-weight: bold; }
        #combo-display { font-size: 0.9em; color: var(--warning-orange); font-weight: bold; margin-top: 5px; height: 1.2em; }

        /* Overlays */
        #game-over-screen, #start-screen {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.85);
            color: white; display: flex; flex-direction: column; justify-content: center; align-items: center;
            z-index: 100; border-radius: 20px; text-align: center; transition: opacity 0.4s ease-in-out; }
        #game-over-screen.hidden-overlay, #start-screen.hidden-overlay { opacity: 0; pointer-events: none; }
        #game-over-screen h2, #start-screen h2 { font-size: 2.8em; margin-bottom: 25px; text-shadow: 1px 1px 4px rgba(0,0,0,0.7); }
        #game-over-screen p, #start-screen p { font-size: 1.3em; margin-bottom: 35px; max-width: 85%; line-height: 1.5; }
        .start-button, .restart-button {
            padding: 14px 30px; font-size: 1.2em; cursor: pointer; background-color: var(--primary-accent);
            color: white; border: none; border-radius: 10px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
        .start-button:hover, .restart-button:hover { background-color: var(--primary-accent-dark); transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.4); }

        /* Timer Bar & Text */
        #timer-container { display: flex; align-items: center; gap: 10px; margin-top: 15px; }
        #timer-bar-container { flex-grow: 1; height: 12px; background-color: var(--component-light-bg); border-radius: 6px; overflow: hidden; }
        #timer-bar { height: 100%; width: 100%; background-color: var(--success-green); border-radius: 6px; transition: width 0.1s linear, background-color 0.3s ease; }
        #timer-bar.medium { background-color: var(--warning-orange); }
        #timer-bar.low { background-color: var(--danger-red); }
        #timer-text { font-size: 0.9em; font-weight: 600; color: var(--text-light); min-width: 65px; text-align: right; }

        /* Action Buttons */
        .action-button {
             width: 100%; padding: 12px; font-size: 1em; border-radius: 8px; color: white;
             border: none; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 3px 6px rgba(0,0,0,0.2);
             font-weight: 500; margin-top: 10px;
        }
         .action-button i { margin-right: 8px; }
         .action-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
         .action-button:disabled { opacity: 0.4; cursor: not-allowed; background-color: #6c757d !important; box-shadow: none; }

         #block-button { background-color: var(--danger-red); }
         #block-button:hover:not(:disabled) { background-color: #c82333; }
         #block-button.blocking { animation: pulse-red 1s infinite; }
          @keyframes pulse-red { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.03); } }

        /* Animiertes Paket */
        #animated-packet { /* Styling für das fliegende Paket */ }
         #animated-packet > svg { width: 18px; height: 18px; }
        #animated-packet.visible { opacity: 1; }
        #animated-packet.blocked { /* Styling wenn blockiert */ }
        @keyframes spin { from { transform: rotate(0deg) scale(1); } to { transform: rotate(360deg) scale(1); } }

    </style>
</head>
<body>
    <a href="index.html" class="absolute top-4 left-4 text-white hover:text-gray-300 z-50 flex items-center gap-2 bg-gray-800 px-3 py-2 rounded-lg opacity-70 hover:opacity-100 transition-opacity no-underline" title="Zur Startseite">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span class="font-medium">Home</span>
    </a>
    <div id="game-container">
        <!-- Linker Bereich: Router-Visualisierung und Ports -->
        <div id="router-area">
            <div id="router-visualization">IP ROUTER</div>
            <div id="ports-container"></div>
            <div id="incoming-packet-area">
                <h4>Eingehendes Paket:</h4>
                <div id="incoming-packet">Warte auf Paket...</div>
                 <div id="timer-container">
                     <div id="timer-bar-container"><div id="timer-bar"></div></div>
                     <div id="timer-text">-- s</div>
                 </div>
            </div>
             <div id="animated-packet"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /> </svg> </div>
        </div>

        <!-- Rechter Bereich: Infos, Tabelle und Aktionen -->
        <div id="info-area">
            <div id="score-container">
                <h3>Spielstand</h3>
                <div id="score-display">Punkte: 0</div>
                <div id="highscore-display">Highscore: 0</div>
                <div id="error-display">Fehler: <span>0</span></div>
                <div id="level-display">Level: 1</div>
                <div id="combo-display">Combo: x1</div> </div>
            <div id="ip-table-container">
                <h3><i class="fas fa-route" style="color: var(--primary-accent); margin-right: 5px;"></i>IP Routing Tabelle</h3> <table id="ip-table">
                    <thead><tr><th>Ziel IP-Netz</th><th>Port</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <button id="block-button" class="action-button" disabled><svg xmlns="http://www.w.org/2000/svg" class="h-4 w-4 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>Blockieren</button>
        </div>

        <!-- Overlays -->
        <div id="start-screen">
            <h2>IP Router Spiel</h2>
            <p>Leite Pakete zum richtigen Port oder Gateway. Blockiere böse Pakete und erreiche den Highscore!</p>
            <button class="start-button">Spiel starten</button>
        </div>
        <div id="game-over-screen" class="hidden-overlay">
            <h2>Game Over!</h2>
            <p>Dein finaler Punktestand: <span id="final-score">0</span></p>
            <button class="restart-button">Neustart</button>
        </div>
    </div>

    <script>
        // --- DOM Elements ---
        const portsContainer = document.getElementById('ports-container');
        const incomingPacketDiv = document.getElementById('incoming-packet');
        const ipTableBody = document.querySelector('#ip-table tbody');
        const scoreDisplay = document.getElementById('score-display');
        const highscoreDisplay = document.getElementById('highscore-display');
        const errorDisplay = document.getElementById('error-display');
        const errorCountSpan = errorDisplay.querySelector('span');
        const levelDisplay = document.getElementById('level-display');
        const comboDisplay = document.getElementById('combo-display');
        const gameOverScreen = document.getElementById('game-over-screen');
        const startScreen = document.getElementById('start-screen');
        const finalScoreSpan = document.getElementById('final-score');
        const startButton = document.querySelector('.start-button');
        const restartButton = document.querySelector('.restart-button');
        const blockButton = document.getElementById('block-button');
        const timerBar = document.getElementById('timer-bar');
        const timerText = document.getElementById('timer-text');
        const animatedPacket = document.getElementById('animated-packet');

        const GATEWAY_PORT_NUMBER = 0;
        const MAX_COMBO = 5;

        // --- Game State (Spielzustand) ---
        let gameState = {
            score: 0,
            highscore: localStorage.getItem('ipRouterHighscore') || 0,
            errors: 0,
            level: 1,
            ports: 3, // Startanzahl Ports
            packetInterval: 5000, // Zeit zwischen Paketen (Startwert)
            decisionTime: 8000, // Zeit zum Entscheiden (Startwert)
            ipTable: new Map(), // Routing Tabelle: Netzwerk -> Port
            currentPacket: null,
            packetTimer: null,
            decisionTimerInterval: null,
            timeLeft: 0,
            gameActive: false,
            errorTimestamp: [], // Um Game Over bei zu vielen Fehlern in kurzer Zeit zu erkennen
            maxErrorsInWindow: 3,
            errorWindowTime: 20000,
            badPacketChance: 0.3, // Wahrscheinlichkeit für böse Pakete
            blockedIPs: new Set(),
            nextNetworkSuffix: 1,
            combo: 1,
        };

        // --- Sound Synthesis (Tone.js) ---
        const synth = new Tone.Synth().toDestination();
        const metalSynth = new Tone.MetalSynth({ frequency: 150, envelope: { attack: 0.001, decay: 0.1, release: 0.1 }, harmonicity: 3.1, modulationIndex: 16, resonance: 2000, octaves: 0.5 }).toDestination();
        const noiseSynth = new Tone.NoiseSynth({ noise: { type: 'white' }, envelope: { attack: 0.005, decay: 0.05, sustain: 0 } }).toDestination();
        function playSound(type) {
             try {
                if (!Tone.context.state || Tone.context.state !== 'running') { Tone.start(); }
                switch(type) {
                    case 'packet': synth.triggerAttackRelease("C4", "16n", Tone.now()); break;
                    case 'correct-port': synth.triggerAttackRelease("G4", "16n", Tone.now() + 0.01); break;
                    case 'correct-gw': synth.triggerAttackRelease("A4", "16n", Tone.now() + 0.01); break;
                    case 'correct-block': metalSynth.triggerAttackRelease("8n", Tone.now() + 0.01); break;
                    case 'error': noiseSynth.triggerAttackRelease("8n", Tone.now()); break;
                    case 'levelup': synth.triggerAttackRelease("C5", "8n", Tone.now()); synth.triggerAttackRelease("G5", "8n", Tone.now() + 0.15); break;
                    case 'combo': synth.triggerAttackRelease(`C${4 + gameState.combo}`, "32n", Tone.now() + 0.01); break;
                }
            } catch (error) { console.error("Tone.js error:", error); }
        }

        // --- Hilfsfunktionen für IP-Logik ---

        /**
         * Generiert eine IP-Adresse für ein bestimmtes Netzwerk.
         * @param {number} networkSuffix - Das dritte Oktett der IP (192.168.X.0).
         * @param {number} hostPart - Das vierte Oktett (Host).
         * @returns {string} Die generierte IP-Adresse.
         */
        function generateIPForNetwork(networkSuffix, hostPart) { return `192.168.${networkSuffix}.${hostPart}`; }

        /**
         * Bestimmt das Netzwerk (CIDR) aus einer IP-Adresse.
         * @param {string} ip - Die zu prüfende IP-Adresse.
         * @returns {string|null} Das Netzwerk (z.B. '192.168.1.0/24') oder 'external'.
         */
        function getNetworkFromIP(ip) {
             if (!ip || typeof ip !== 'string') return null;
             const parts = ip.split('.');
             if (parts.length !== 4) return null;
             if (parts[0] === '192' && parts[1] === '168') { return `${parts[0]}.${parts[1]}.${parts[2]}.0/24`; }
             return 'external';
        }
        function generateRandomIP(forceExternal = false) {
             const type = Math.random();
             if (forceExternal || type < 0.5) { return `${Math.floor(Math.random() * 191) + 1}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 254) + 1}`; }
             else { // IP aus einem *existierenden* lokalen Netzwerk generieren
                 const knownNetworks = Array.from(gameState.ipTable.keys());
                 if (knownNetworks.length === 0) return generateRandomIP(true);
                 const targetNetwork = knownNetworks[Math.floor(Math.random() * knownNetworks.length)];
                 const networkSuffix = parseInt(targetNetwork.split('.')[2]);
                 return generateIPForNetwork(networkSuffix, Math.floor(Math.random() * 254) + 1);
             }
        }

        // --- Spielmechanik (Punkte, Level, Combo) ---
        function updateScore(points) {
            const pointsToAdd = points * gameState.combo;
            gameState.score += pointsToAdd;
            scoreDisplay.textContent = `Punkte: ${gameState.score}`;
            if (gameState.score > gameState.highscore) { gameState.highscore = gameState.score; highscoreDisplay.textContent = `Highscore: ${gameState.highscore}`; localStorage.setItem('ipRouterHighscore', gameState.highscore); }
            if (gameState.score >= gameState.level * 100) { levelUp(); }
        }
        function increaseCombo() {
            gameState.combo = Math.min(MAX_COMBO, gameState.combo + 1);
            comboDisplay.textContent = `Combo: x${gameState.combo}`;
             if (gameState.combo > 1) playSound('combo');
        }
        function resetCombo() {
            gameState.combo = 1; comboDisplay.textContent = `Combo: x${gameState.combo}`;
        }
        function levelUp() {
            playSound('levelup'); gameState.level++; levelDisplay.textContent = `Level: ${gameState.level}`;
            if (gameState.ports < 12 || (gameState.ports < 32 && gameState.level % 2 === 0)) { addPort(); }
            // Schwierigkeit erhöhen
            gameState.packetInterval = Math.max(1500, gameState.packetInterval - 400);
            gameState.decisionTime = Math.max(4000, gameState.decisionTime - 500);
            gameState.badPacketChance = Math.min(0.4, gameState.badPacketChance + 0.90);
            if (gameState.level % 2 === 0) { const newBlocked = generateRandomIP(true); gameState.blockedIPs.add(newBlocked); }
            clearTimeout(gameState.packetTimer); if(gameState.gameActive) { gameState.packetTimer = setTimeout(generatePacket, gameState.packetInterval); }
        }
        function addPort() {
            gameState.ports++;
            const newPortNumber = gameState.ports;
            const newNetwork = `192.168.${gameState.nextNetworkSuffix}.0/24`;
            gameState.ipTable.set(newNetwork, newPortNumber);
            gameState.nextNetworkSuffix++;
            createPorts();
            updateIpTableDisplay();
        }

        function handleError() {
            playSound('error'); resetCombo(); gameState.errors++; errorCountSpan.textContent = gameState.errors;
            updateScore(-15); const now = Date.now(); gameState.errorTimestamp.push(now);
            gameState.errorTimestamp = gameState.errorTimestamp.filter(ts => now - ts < gameState.errorWindowTime);
            if (gameState.errorTimestamp.length >= gameState.maxErrorsInWindow) { gameOver(); }
        }
        function startDecisionTimer() {
             clearInterval(gameState.decisionTimerInterval); gameState.timeLeft = gameState.decisionTime; updateTimerBar();
             gameState.decisionTimerInterval = setInterval(() => {
                 gameState.timeLeft -= 100; updateTimerBar();
                 if (gameState.timeLeft <= 0) {
                     clearInterval(gameState.decisionTimerInterval); if (gameState.currentPacket) { handleError(); clearIncomingPacket(true); clearTimeout(gameState.packetTimer); gameState.packetTimer = setTimeout(generatePacket, 1000); }
                 }
             }, 100);
        }
        function updateTimerBar() {
             const percentage = Math.max(0, (gameState.timeLeft / gameState.decisionTime) * 100); timerBar.style.width = `${percentage}%`; timerText.textContent = `${(Math.max(0, gameState.timeLeft) / 1000).toFixed(1)}s`;
             timerBar.classList.remove('low', 'medium'); if (percentage < 30) { timerBar.classList.add('low'); } else if (percentage < 60) { timerBar.classList.add('medium'); }
        }
        function updateIpTableDisplay() {
             ipTableBody.innerHTML = ''; const gwRow = ipTableBody.insertRow(); gwRow.insertCell(0).textContent = '0.0.0.0/0 (Default)'; gwRow.insertCell(1).textContent = `GW Port`;
             gameState.ipTable.forEach((port, network) => { const row = ipTableBody.insertRow(); row.insertCell(0).textContent = network; row.insertCell(1).textContent = `Port ${port}`; });
        }

        // --- Paket-Logik ---

        /**
         * Generiert ein neues Spiel-Paket.
         * Erstellt zufällige Quell- und Ziel-IPs und bestimmt den korrekten Zielport.
         * Kann auch "böse" Pakete generieren, die blockiert werden müssen.
         */
        function generatePacket() {
             if (!gameState.gameActive) return;
            clearIncomingPacket();
            playSound('packet');
            const incomingPort = Math.floor(Math.random() * gameState.ports) + 1;
            const srcIP = generateRandomIP();
            let destIP = generateRandomIP();
            let packetType = 'external';
            let targetPort = GATEWAY_PORT_NUMBER;
            let isBadPacket = false;
            let destNetwork = getNetworkFromIP(destIP);

            // 1. Prüfen ob Source IP blockiert ist
            if (gameState.blockedIPs.has(srcIP) && Math.random() < gameState.badPacketChance) {
                isBadPacket = true;
                packetType = 'bad';
                targetPort = null; // Ziel ist Blockieren
            } else {
                // 2. Prüfen des Ziel-Netzwerks
                if (destNetwork === 'external') {
                    packetType = 'external';
                    targetPort = GATEWAY_PORT_NUMBER;
                } else { // Lokales Netzwerk (192.168.x.x)
                    if (gameState.ipTable.has(destNetwork)) {
                        packetType = 'normal'; // Bekanntes lokales Netz
                        targetPort = gameState.ipTable.get(destNetwork);
                    } else {
                        // Unbekanntes lokales Netz -> Gateway
                        console.warn(`Packet für unbekanntes lokales Netz ${destNetwork}, Routing zu Gateway.`);
                        packetType = 'external';
                        targetPort = GATEWAY_PORT_NUMBER;
                    }
                }
            }

            gameState.currentPacket = { id: Date.now(), srcIP, destIP, destNetwork, incomingPort, type: packetType, targetPort };
            displayPacket(gameState.currentPacket);
            startDecisionTimer();
            blockButton.disabled = packetType !== 'bad';
        }

        function displayPacket(packet) {
            let icon = '❓'; let iconColorClass = ''; let typeText = '';
            switch (packet.type) {
                case 'external': icon = '☁️'; iconColorClass = 'external'; typeText = ' (-> Gateway)'; break;
                case 'bad': icon = '💀'; iconColorClass = 'bad'; typeText = ' (Böses Paket!)'; break;
                case 'normal': icon = '💻'; break;
            }
            incomingPacketDiv.innerHTML = `
                <div class="packet-icon ${iconColorClass}">${icon}</div>
                <div><strong>Von Port:</strong> ${packet.incomingPort}</div>
                <div><strong>Src IP:</strong> ${packet.srcIP} ${packet.type === 'bad' ? '<span class="text-red-600 font-bold">[BLOCK!]</span>':''}</div>
                <div><strong>Dest IP:</strong> ${packet.destIP}${typeText}</div>`;
        }

        function clearIncomingPacket(showError = false) {
             incomingPacketDiv.innerHTML = showError ? `<span style="color: var(--danger-red); font-weight: bold;">Fehler!</span>` : 'Warte auf Paket...';
             clearInterval(gameState.decisionTimerInterval);
             gameState.currentPacket = null;
             blockButton.disabled = true;
             timerBar.style.width = '0%';
             timerText.textContent = '-- s';
        }

        // --- Interaktion & Animation ---
        function getElementCenter(element) {
            if (!element) return { x: 0, y: 0 }; const rect = element.getBoundingClientRect(); const containerRect = document.getElementById('router-area').getBoundingClientRect(); return { x: rect.left - containerRect.left + rect.width / 2, y: rect.top - containerRect.top + rect.height / 2 }; }
        function animatePacketTo(targetElement, packetType = 'normal') {
            return new Promise(resolve => { const startPos = getElementCenter(incomingPacketDiv); const endPos = getElementCenter(targetElement); const packet = animatedPacket; packet.className = 'animated-packet'; packet.style.left = `${startPos.x - 17}px`; packet.style.top = `${startPos.y - 12}px`; packet.style.opacity = '0'; packet.style.transform = 'scale(0.5)'; requestAnimationFrame(() => { packet.classList.add('visible'); packet.style.transform = 'scale(1)'; setTimeout(() => { packet.style.left = `${endPos.x - 17}px`; packet.style.top = `${endPos.y - 12}px`; }, 10); }); setTimeout(() => { packet.style.opacity = '0'; packet.style.transform = 'scale(0.5)'; resolve(); }, 700); }); }
        function animatePacketSpecial(type) {
             return new Promise(resolve => { const startPos = getElementCenter(incomingPacketDiv); const packet = animatedPacket; packet.className = 'animated-packet'; packet.style.left = `${startPos.x - 17}px`; packet.style.top = `${startPos.y - 12}px`; packet.style.opacity = '0'; packet.style.transform = 'scale(0.5)'; requestAnimationFrame(() => { packet.classList.add('visible'); packet.style.transform = 'scale(1)'; if (type === 'block') { packet.classList.add('blocked'); } }); setTimeout(() => { packet.style.opacity = '0'; packet.style.transform = 'scale(0.5)'; packet.classList.remove('blocked'); resolve(); }, 700); }); }

        function handlePortClick(clickedPortNumber) {
             if (!gameState.currentPacket || !gameState.gameActive || isAnimating) return;
            const packet = gameState.currentPacket;
            let correctAction = false;
            let targetPortElement = document.querySelector(`.port[data-port="${clickedPortNumber}"]`);

            // Korrekt, wenn Paket 'normal'/'external' ist UND Zielport übereinstimmt
            if ((packet.type === 'normal' && packet.targetPort === clickedPortNumber) ||
                (packet.type === 'external' && clickedPortNumber === GATEWAY_PORT_NUMBER)) {
                 correctAction = true;
                 updateScore(10);
                 playSound(clickedPortNumber === GATEWAY_PORT_NUMBER ? 'correct-gw' : 'correct-port');
                 increaseCombo();
            }
            finalizeAction(correctAction, targetPortElement);
        }

        blockButton.addEventListener('click', () => {
            if (!gameState.currentPacket || !gameState.gameActive || isAnimating) return;
            let correctAction = false;
             if (gameState.currentPacket.type === 'bad') {
                 updateScore(20); playSound('correct-block'); increaseCombo(); correctAction = true;
                 finalizeAction(correctAction, null, false, true); // Block-Flag setzen
                 return;
             }
             finalizeAction(false, null); // Falsche Aktion (unschuldiges Paket blockiert)
        });

        let isAnimating = false;

        async function finalizeAction(isCorrect, portElement, isBroadcast = false, isBlock = false, isDiscover = false) {
             if (isAnimating) return;
             isAnimating = true;
             clearInterval(gameState.decisionTimerInterval);

            if (isCorrect) {
                if (portElement) { await animatePacketTo(portElement); portElement.classList.add('correct'); }
                else if (isBlock) { await animatePacketSpecial('block'); blockButton.classList.add('blocking'); }

                clearIncomingPacket();
                 clearTimeout(gameState.packetTimer);
                 gameState.packetTimer = setTimeout(generatePacket, Math.max(800, gameState.packetInterval * 0.8)); // Schnelleres nächstes Paket

            } else { // Falsche Aktion
                 incomingPacketDiv.parentElement.classList.add('shake-error');
                 if (portElement) { portElement.classList.add('incorrect'); }
                 handleError();
                 clearTimeout(gameState.packetTimer);
                 gameState.packetTimer = setTimeout(() => {
                     clearIncomingPacket(true);
                     gameState.packetTimer = setTimeout(generatePacket, gameState.packetInterval); // Normale Verzögerung nach Fehler
                 }, 1500);
            }

             setTimeout(() => {
                 document.querySelectorAll('.port.correct, .port.incorrect').forEach(p => p.classList.remove('correct', 'incorrect'));
                 blockButton.classList.remove('blocking');
                 incomingPacketDiv.parentElement.classList.remove('shake-error');
                 isAnimating = false;
             }, 700);
        }

        // --- Spielablauf & Setup ---
        function createPorts() {
             portsContainer.innerHTML = '';
            const normalPorts = gameState.ports;
            let columns = 4;
             if (normalPorts > 12) columns = 8; else if (normalPorts > 6) columns = 6; else if (normalPorts > 3) columns = 4; else columns = 3;
            portsContainer.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
            for (let i = 1; i <= normalPorts; i++) {
                const port = document.createElement('div'); port.classList.add('port');
                // Finde das Netzwerk, das diesem Port zugeordnet ist
                const networkEntry = Array.from(gameState.ipTable.entries()).find(([net, p]) => p === i);
                const displayNetwork = networkEntry ? networkEntry[0].split('/')[0] : 'FEHLER';
                port.innerHTML = `<span class="port-number">P ${i}</span><span class="port-ip">${displayNetwork}</span>`;
                port.dataset.port = i; port.addEventListener('click', () => handlePortClick(i)); portsContainer.appendChild(port);
            }
             const gatewayPort = document.createElement('div'); gatewayPort.classList.add('port', 'gateway');
             gatewayPort.innerHTML = `<span class="port-number"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.09M10.5 8a2.5 2.5 0 012.41 2.083l.42 2.104a2.5 2.5 0 002.41 2.083H18.5a2.5 2.5 0 002.5-2.5v-1.293a2.5 2.5 0 00-.732-1.767l-2.06-1.716a2.5 2.5 0 00-1.767-.732H10.5z" /></svg> GW</span><span class="port-ip">Internet</span>`;
             gatewayPort.dataset.port = GATEWAY_PORT_NUMBER; gatewayPort.addEventListener('click', () => handlePortClick(GATEWAY_PORT_NUMBER)); portsContainer.appendChild(gatewayPort);
        }
        function resetGameState() {
             gameState.score = 0; gameState.errors = 0; gameState.level = 1; gameState.ports = 3;
             gameState.packetInterval = 5000;
             gameState.decisionTime = 8000;
             gameState.ipTable.clear();
             gameState.currentPacket = null; gameState.errorTimestamp = []; gameState.badPacketChance = 0;
             gameState.blockedIPs.clear(); gameState.nextNetworkSuffix = 1; gameState.combo = 1;
             clearTimeout(gameState.packetTimer); clearInterval(gameState.decisionTimerInterval);

             // Initiale Netzwerke erstellen und Routen hinzufügen
             for(let i = 1; i <= gameState.ports; i++) {
                 const network = `192.168.${gameState.nextNetworkSuffix}.0/24`;
                 gameState.ipTable.set(network, i);
                 gameState.nextNetworkSuffix++;
             }
             comboDisplay.textContent = `Combo: x${gameState.combo}`;
        }
        function startGame() {
             resetGameState(); gameState.gameActive = true;
             startScreen.classList.add('hidden-overlay'); gameOverScreen.classList.add('hidden-overlay');
             scoreDisplay.textContent = `Punkte: ${gameState.score}`; highscoreDisplay.textContent = `Highscore: ${gameState.highscore}`;
             errorCountSpan.textContent = gameState.errors; levelDisplay.textContent = `Level: ${gameState.level}`;
             updateIpTableDisplay(); createPorts(); clearIncomingPacket();
             gameState.packetTimer = setTimeout(generatePacket, 1500);
        }
        function gameOver() {
             gameState.gameActive = false;
             clearTimeout(gameState.packetTimer); clearInterval(gameState.decisionTimerInterval);
             finalScoreSpan.textContent = gameState.score;
             gameOverScreen.classList.remove('hidden-overlay'); startScreen.classList.add('hidden-overlay');
        }

        // --- Event Listeners ---
        startButton.addEventListener('click', startGame);
        restartButton.addEventListener('click', startGame);

        // --- Initiales Setup ---
        highscoreDisplay.textContent = `Highscore: ${gameState.highscore}`;
        gameOverScreen.classList.add('hidden-overlay');
        startScreen.classList.remove('hidden-overlay');

    </script>
</body>
</html>
