<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webseitenaufruf</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            height: 100vh;
            overflow: hidden;
        }
        .main-container {
             height: calc(100vh - 110px); /* Adjusted for slightly smaller header/footer */
             overflow-y: auto;
        }

        :root {
            --stadt-zuerich-blue: #00559a;
        }
        .stadt-blue { color: var(--stadt-zuerich-blue); }
        .bg-stadt-blue { background-color: var(--stadt-zuerich-blue); }
        .border-stadt-blue { border-color: var(--stadt-zuerich-blue); }

        /* Steps Styling */
        .step {
            background-color: white;
            border-left: 4px solid #cbd5e1;
            transition: all 0.3s ease-in-out;
            padding: 12px;
            margin-bottom: 8px !important;
        }
         .step h2 { font-size: 1.1rem; margin-bottom: 4px; }
         .step p { font-size: 0.85rem; margin-bottom: 8px; }
         .step .details { font-size: 0.8rem; margin-bottom: 8px; padding-left: 8px; }
         .step button { padding: 6px 12px; font-size: 0.8rem; }
        .step.correct { border-left-color: #22c55e; }
         .step.incorrect-shake { animation: shake 0.5s ease-in-out; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); } 75% { transform: translateX(-5px); }
        }
        .step-button:disabled { opacity: 0.6; cursor: not-allowed; background-color: #94a3b8; }
        .step-button:not(:disabled):hover { background-color: #00447a; }

        /* Visualization Area */
        #visualization {
            background-color: #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            position: relative;
            min-height: 400px;
            height: 100%;
            overflow: hidden;
        }
        .node {
            position: absolute;
            text-align: center;
            font-size: 0.75rem;
            width: 70px;
            z-index: 10;
        }
        .node i {
            font-size: 1.6rem;
            display: block;
            margin-bottom: 2px;
            color: var(--stadt-zuerich-blue);
            transition: color 0.3s ease;
        }
        .node.active i { color: #f59e0b; }
        /* Node Positions */
        #client { top: 45%; left: 5%; }
        #resolver { top: 10%; left: 25%; }
        #root-dns { top: 10%; left: 45%; }
        #tld-dns { top: 10%; left: 65%; }
        #auth-dns { top: 45%; left: 80%; }
        #webserver { top: 80%; left: 50%; }

        /* Connection Lines */
        .connection-line {
            position: absolute;
            border-top: 2px dashed #94a3b8;
            transform-origin: 0 0;
            opacity: 0.3;
            transition: all 0.5s ease-in-out;
            z-index: 1;
        }
        .connection-line.active { opacity: 1; border-color: var(--stadt-zuerich-blue); border-style: solid; }

        /* Packet Animation */
        .packet-container {
             position: absolute;
             z-index: 5;
             opacity: 0;
             /* Adjusted transition duration to be dynamically set by JS */
             transition: opacity 0.2s ease-in-out;
             display: flex;
             align-items: center;
             pointer-events: none;
        }
        .packet-container.visible { opacity: 1; }
        .packet {
            width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
            background-color: #dc2626; /* Red */
            box-shadow: 0 0 5px rgba(220, 38, 38, 0.7);
        }
        .packet.response {
             background-color: #16a34a; /* Green */
             box-shadow: 0 0 5px rgba(22, 163, 74, 0.7);
        }
        .packet-label {
            font-size: 0.7rem; color: #334155; background-color: rgba(255, 255, 255, 0.7);
            padding: 1px 4px; border-radius: 3px; margin-left: 5px; white-space: nowrap;
        }

        /* Server Action Text */
        .action-text {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.65);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            z-index: 15;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
            white-space: nowrap;
        }
        .action-text.visible {
            opacity: 1;
        }

        /* Feedback & Time */
        #feedback { transition: opacity 0.5s ease-in-out; min-height: 40px; font-size: 0.9rem; }
        #time-display { font-size: 0.9rem; font-weight: 500; color: white; /* Changed color for header */ }
    </style>
</head>
<body class="flex flex-col h-screen">

    <header class="p-3 bg-stadt-blue text-white flex items-center space-x-3 flex-shrink-0"> <svg class="w-7 h-7" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="white"> <path d="M50,5 L95,27.5 L95,72.5 L50,95 L5,72.5 L5,27.5 Z M50,15 L85,32.5 v35 L50,85 L15,67.5 v-35 Z M50,25 L75,37.5 v25 L50,75 L25,62.5 v-25 Z"/>
         </svg>
        <div>
            <h1 class="text-lg md:text-xl font-bold">BIT 2025 | Netzwerk</h1> <p class="text-xs opacity-90">Der Weg zu https://www.stadt-zuerich.ch/</p>
        </div>
        <div id="time-display" class="ml-auto pr-3">Gesamtzeit: 0.0 s</div>
    </header>

    <div class="main-container flex-grow">
        <main class="p-4 grid grid-cols-1 md:grid-cols-5 gap-4 h-full">

            <div class="md:col-span-2 space-y-2 overflow-y-auto pr-2">
                <p class="text-gray-700 text-sm">Was passiert zuerst? Klicke auf den richtigen Schritt.</p>
                <div id="feedback" class="p-2 rounded-md border text-center font-medium">Wähle den ersten Schritt!</div>
                <div id="simulationSteps" class="space-y-2"></div>
                <p id="completionMessage" class="mt-4 text-green-600 font-semibold hidden text-center">Super! Simulation abgeschlossen!</p>
            </div>

            <div id="visualization" class="md:col-span-3 relative h-full">
                <div id="client" class="node"><i class="fas fa-laptop"></i>Dein Computer</div>
                <div id="resolver" class="node"><i class="fas fa-server"></i>DNS Resolver</div>
                <div id="root-dns" class="node"><i class="fas fa-server"></i>Root DNS</div>
                <div id="tld-dns" class="node"><i class="fas fa-server"></i>.ch DNS</div>
                <div id="auth-dns" class="node"><i class="fas fa-server"></i>Stadt ZH DNS</div>
                <div id="webserver" class="node"><i class="fas fa-server"></i>Stadt ZH Webserver</div>

                <div id="packet-container" class="packet-container hidden">
                    <div id="packet" class="packet"></div>
                    <span id="packet-label" class="packet-label"></span>
                </div>
                <div id="action-text" class="action-text hidden"></div>
            </div>
        </main>
    </div>

    <script>
        const feedback = document.getElementById('feedback');
        const simulationStepsContainer = document.getElementById('simulationSteps');
        const completionMessage = document.getElementById('completionMessage');
        const visualization = document.getElementById('visualization');
        const packetContainer = document.getElementById('packet-container');
        const packetElement = document.getElementById('packet');
        const packetLabel = document.getElementById('packet-label');
        const timeDisplay = document.getElementById('time-display');
        const actionTextElement = document.getElementById('action-text');

        // Define steps with slower durations and server actions
        const stepsData = [
            { id: 'dns', title: 'DNS-Auflösung', description: 'IP-Adresse finden.', details: ['Cache?', '-> Resolver', 'Resolver -> Root', 'Root -> .ch', '.ch -> Stadt ZH', 'IP erhalten!'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: [
                { from: 'client', to: 'resolver', duration: 1500, type: 'request', content: 'DNS Query?' },
                { from: 'resolver', to: 'root-dns', duration: 1000, type: 'request', content: '?', actionText: 'Frage Root...' }, // Action text added
                { from: 'root-dns', to: 'resolver', duration: 1000, type: 'response', content: '.ch Server', actionText: 'Verweise zu .ch' },
                { from: 'resolver', to: 'tld-dns', duration: 1000, type: 'request', content: '?', actionText: 'Frage .ch...' },
                { from: 'tld-dns', to: 'resolver', duration: 1000, type: 'response', content: 'StadtZH DNS', actionText: 'Verweise zu StadtZH' },
                { from: 'resolver', to: 'auth-dns', duration: 1200, type: 'request', content: '?', actionText: 'Frage StadtZH...' },
                { from: 'auth-dns', to: 'resolver', duration: 1200, type: 'response', content: 'IP Addr', actionText: 'Gebe IP zurück' },
                { from: 'resolver', to: 'client', duration: 1500, type: 'response', content: 'IP Addr', actionText: 'Sende IP zu Client' },
              ]
            },
            { id: 'tcp', title: 'TCP-Verbindung', description: 'Stabile Verbindung aufbauen.', details: ['SYN', 'SYN-ACK', 'ACK', 'Verbunden!'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: [
                { from: 'client', to: 'webserver', duration: 1500, type: 'request', content: 'SYN' },
                { from: 'webserver', to: 'client', duration: 1500, type: 'response', content: 'SYN-ACK', actionText: 'Bestätige SYN' },
                { from: 'client', to: 'webserver', duration: 1500, type: 'request', content: 'ACK' },
              ]
            },
            { id: 'tls', title: 'Sichere Verbindung (TLS)', description: 'Verbindung verschlüsseln.', details: ['Client Hello', 'Server Hello+Cert', 'Prüfung', 'Schlüssel', 'Sicher!'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: [
                 { from: 'client', to: 'webserver', duration: 1500, type: 'request', content: 'Hello' },
                 { from: 'webserver', to: 'client', duration: 1500, type: 'response', content: 'Cert', actionText: 'Sende Zertifikat' },
                 { from: 'client', to: 'webserver', duration: 1000, type: 'request', content: 'Key Exch', actionText: 'Prüfe Cert...' }, // Client "action"
                 { from: 'webserver', to: 'client', duration: 1000, type: 'response', content: 'OK', actionText: 'Verschlüsselung OK' },
              ]
            },
            { id: 'http-req', title: 'HTTP-Anfrage', description: 'Webseite anfordern.', details: ['GET /', 'Host: ...', 'Gesendet.'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: [
                 { from: 'client', to: 'webserver', duration: 1800, type: 'request', content: 'GET /' }
              ]
            },
            { id: 'http-res', title: 'HTTP-Antwort', description: 'Daten empfangen.', details: ['200 OK', 'HTML...', 'Empfangen.'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: [
                 { from: 'webserver', to: 'client', duration: 1800, type: 'response', content: 'HTML...', actionText: 'Sende Webseite' }
              ]
            },
            { id: 'render', title: 'Webseite darstellen', description: 'Seite anzeigen.', details: ['HTML lesen', 'CSS anwenden', 'JS ausführen', 'Fertig!'], correct: false, element: null, buttonElement: null, detailsElement: null,
              animationSequence: []
            }
        ];

        let currentCorrectStepIndex = 0;
        let isAnimating = false;
        let totalElapsedTime = 0;
        let actionTextTimeout = null; // Timeout for hiding action text

        function showFeedback(message, type = 'info') { /* ... (same as before) ... */
             feedback.textContent = message;
            feedback.className = 'p-2 rounded-md border text-center font-medium '; // Reset classes
            if (type === 'error') {
                feedback.classList.add('bg-red-100', 'border-red-300', 'text-red-800');
            } else if (type === 'success') {
                 feedback.classList.add('bg-green-100', 'border-green-300', 'text-green-800');
            } else { // info
                feedback.classList.add('bg-blue-100', 'border-blue-300', 'text-blue-800');
            }
        }
        function updateTimeDisplay() { /* ... (same as before) ... */
            timeDisplay.textContent = `Gesamtzeit: ${(totalElapsedTime / 1000).toFixed(1)} s`;
        }
        function getNodeCenter(nodeId) { /* ... (same as before) ... */
            const node = document.getElementById(nodeId);
            if (!node) return { x: 0, y: 0 };
            const rect = node.getBoundingClientRect();
            const containerRect = visualization.getBoundingClientRect();
            return {
                x: rect.left - containerRect.left + rect.width / 2,
                y: rect.top - containerRect.top + rect.height / 2
            };
        }

        // Function to show/hide server action text
        function showActionText(nodeId, text) {
            clearTimeout(actionTextTimeout); // Clear previous timeout if any
            const nodePos = getNodeCenter(nodeId);
            actionTextElement.textContent = text;
            // Position above the node icon
            actionTextElement.style.left = `${nodePos.x}px`;
            actionTextElement.style.top = `${nodePos.y - 40}px`; // Adjust vertical position
            actionTextElement.style.transform = 'translateX(-50%)'; // Center horizontally
            actionTextElement.classList.add('visible');
        }

        function hideActionText(delay = 0) {
             clearTimeout(actionTextTimeout);
             actionTextTimeout = setTimeout(() => {
                 actionTextElement.classList.remove('visible');
             }, delay);
        }


        // Function to animate a single packet movement
        function animatePacket(fromId, toId, duration, type = 'request', content = '', actionText = '') {
            return new Promise(resolve => {
                const startPos = getNodeCenter(fromId);
                const endPos = getNodeCenter(toId);

                // Show action text near the *sending* node before animation starts
                if (actionText) {
                    showActionText(fromId, actionText);
                }

                packetElement.className = 'packet';
                if (type === 'response') packetElement.classList.add('response');
                packetLabel.textContent = content;
                packetContainer.className = 'packet-container visible';

                packetContainer.style.left = `${startPos.x - 6}px`;
                packetContainer.style.top = `${startPos.y - 10}px`;
                // Set transition duration dynamically
                packetContainer.style.transition = `top ${duration / 1000}s ease-in-out, left ${duration / 1000}s ease-in-out, opacity 0.2s ease-in-out`;


                 visualization.querySelectorAll('.node').forEach(n => n.classList.remove('active'));
                 document.getElementById(fromId)?.classList.add('active');
                 document.getElementById(toId)?.classList.add('active');

                 visualization.querySelectorAll('.connection-line').forEach(l => l.classList.remove('active'));
                 const lineId = `line-${fromId}-${toId}`.replace(/-dns/g,'');
                 const reverseLineId = `line-${toId}-${fromId}`.replace(/-dns/g,'');
                 const lineElement = document.getElementById(lineId) || document.getElementById(reverseLineId);
                 if(lineElement) lineElement.classList.add('active');

                void packetContainer.offsetWidth;

                requestAnimationFrame(() => {
                     packetContainer.style.left = `${endPos.x - 6}px`;
                     packetContainer.style.top = `${endPos.y - 10}px`;
                });

                setTimeout(() => {
                    packetContainer.classList.remove('visible');
                    // Hide action text shortly after packet arrives
                    if (actionText) {
                         hideActionText(500); // Hide after 0.5s
                    }
                     document.getElementById(fromId)?.classList.remove('active');
                     document.getElementById(toId)?.classList.remove('active');
                     if(lineElement) lineElement.classList.remove('active');
                    resolve();
                }, duration + 50);
            });
        }

        // Function to run a sequence of animations and update time
        async function runAnimationSequence(sequence) {
            isAnimating = true;
            hideActionText(); // Hide any previous action text immediately
            let sequenceDuration = 0;
            const stepDelay = 300; // Pause between animation steps in ms

            for (const step of sequence) {
                await animatePacket(step.from, step.to, step.duration, step.type, step.content, step.actionText);
                sequenceDuration += step.duration;
                // Add a small pause after each packet animation (except the last one)
                if (sequence.indexOf(step) < sequence.length - 1) {
                     await new Promise(resolve => setTimeout(resolve, stepDelay));
                     sequenceDuration += stepDelay;
                }
            }
            totalElapsedTime += sequenceDuration;
            updateTimeDisplay();
            isAnimating = false;
        }


        // Function to handle step click
        async function handleStepClick(clickedIndex) { /* ... (mostly same as before) ... */
             if (isAnimating) {
                 showFeedback('Bitte warte, bis die Animation abgeschlossen ist.', 'info');
                 return;
             }

            const clickedStep = stepsData[clickedIndex];

            stepsData.forEach(step => {
                 if(step.element) step.element.classList.remove('incorrect-shake');
            });

            if (clickedIndex === currentCorrectStepIndex) {
                clickedStep.correct = true;
                clickedStep.element.classList.add('correct');
                 if (clickedStep.buttonElement) clickedStep.buttonElement.disabled = true;
                 if (clickedStep.detailsElement) {
                    clickedStep.detailsElement.classList.remove('hidden');
                 }

                showFeedback(`Richtig! Starte "${clickedStep.title}"...`, 'success');

                await runAnimationSequence(clickedStep.animationSequence); // Run the updated sequence function

                currentCorrectStepIndex++;

                if (currentCorrectStepIndex === stepsData.length) {
                    completionMessage.classList.remove('hidden');
                    showFeedback('Super! Alle Schritte in der richtigen Reihenfolge gefunden!', 'success');
                     const renderStep = stepsData.find(s => s.id === 'render');
                     if (renderStep && renderStep.detailsElement) {
                         renderStep.detailsElement.classList.remove('hidden');
                         renderStep.element.classList.add('correct');
                     }
                } else {
                     // Add a slight delay before showing the next prompt to avoid overlap with hiding action text
                     setTimeout(() => {
                        showFeedback(`Was passiert als Nächstes? (Schritt ${currentCorrectStepIndex + 1})`, 'info');
                     }, 600);
                }

            } else if (clickedIndex < currentCorrectStepIndex) {
                 showFeedback(`Schritt "${clickedStep.title}" ist bereits erledigt.`, 'info');
            }
            else {
                showFeedback(`"${clickedStep.title}" ist noch nicht der richtige Schritt. Überlege nochmal!`, 'error');
                clickedStep.element.classList.add('incorrect-shake');
            }
        }

         // Function to create connection lines dynamically
         function createConnectionLine(id1, id2) { /* ... (same as before) ... */
            const node1 = document.getElementById(id1);
            const node2 = document.getElementById(id2);
            if (!node1 || !node2) return;

            const pos1 = getNodeCenter(id1);
            const pos2 = getNodeCenter(id2);

            const dx = pos2.x - pos1.x;
            const dy = pos2.y - pos1.y;
            const length = Math.sqrt(dx * dx + dy * dy);
            const angle = Math.atan2(dy, dx) * (180 / Math.PI);

            const lineId = `line-${id1}-${id2}`.replace(/-dns/g,''); // Use cleaned ID
            const reverseLineId = `line-${id2}-${id1}`.replace(/-dns/g,'');
            if (document.getElementById(lineId) || document.getElementById(reverseLineId)) {
                return;
            }

            const line = document.createElement('div');
            line.id = lineId;
            line.className = 'connection-line';
            line.style.left = `${pos1.x}px`;
            line.style.top = `${pos1.y}px`;
            line.style.width = `${length}px`;
            line.style.transform = `rotate(${angle}deg)`;

            visualization.appendChild(line);
         }

        // Function to create all necessary lines
        function createAllLines() { /* ... (same as before) ... */
            const connections = new Set();
            stepsData.forEach(step => {
                step.animationSequence.forEach(anim => {
                    // Find original IDs including '-dns' if needed for getNodeCenter lookup later
                    const originalFromId = stepsData.flatMap(s => s.animationSequence).map(a=>a.from).find(id => id.replace(/-dns/g,'') === anim.from.replace(/-dns/g,'')) || anim.from;
                     const originalToId = stepsData.flatMap(s => s.animationSequence).map(a=>a.to).find(id => id.replace(/-dns/g,'') === anim.to.replace(/-dns/g,'')) || anim.to;

                    const pair = [originalFromId, originalToId].sort().join('-'); // Create unique key for pair using original IDs
                    connections.add(pair);
                });
            });

             connections.forEach(pair => {
                const [id1, id2] = pair.split('-');
                createConnectionLine(id1, id2);
             });
        }


        // Function to create and shuffle step elements
        function setupSteps() { /* ... (same as before, ensures buttons call handleStepClick with correct original index) ... */
             stepsData.forEach((step, index) => {
                const stepElement = document.createElement('section');
                stepElement.id = `step-${step.id}`;
                stepElement.className = 'step rounded-lg shadow-sm';

                const title = document.createElement('h2');
                title.className = 'text-lg font-semibold stadt-blue mb-1';
                title.textContent = step.title;

                const description = document.createElement('p');
                description.className = 'text-gray-600 mb-2 text-sm';
                description.textContent = step.description;

                const detailsDiv = document.createElement('div');
                detailsDiv.id = `${step.id}-details`;
                detailsDiv.className = 'details hidden space-y-1 text-gray-500 mb-2 pl-2 border-l-2 border-gray-200';
                step.details.forEach(detailText => {
                    const p = document.createElement('p');
                    p.textContent = detailText;
                    detailsDiv.appendChild(p);
                });

                stepElement.appendChild(title);
                stepElement.appendChild(description);
                stepElement.appendChild(detailsDiv);

                 if (step.id !== 'render') {
                     const button = document.createElement('button');
                     button.id = `${step.id}Button`;
                     button.className = 'step-button bg-stadt-blue text-white font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00447a]';
                     button.textContent = 'Ausführen';
                     button.addEventListener('click', () => handleStepClick(index)); // Use original index
                     stepElement.appendChild(button);
                     step.buttonElement = button;
                 }

                step.element = stepElement;
                step.detailsElement = detailsDiv;
            });

            const stepsWithOriginalIndex = stepsData.map((step, index) => ({ ...step, originalIndex: index }));
            const shuffledSteps = stepsWithOriginalIndex.sort(() => Math.random() - 0.5);

            shuffledSteps.forEach(step => {
                 if(step.buttonElement) {
                    const newButton = step.buttonElement.cloneNode(true);
                    step.element.replaceChild(newButton, step.buttonElement);
                    // Attach listener using the original index stored in the shuffled object
                    newButton.addEventListener('click', () => handleStepClick(step.originalIndex));
                    step.buttonElement = newButton;
                 }
                simulationStepsContainer.appendChild(step.element);
            });
        }

        // Initial Setup
        setupSteps();
        createAllLines();
        updateTimeDisplay();
        showFeedback('Wähle den ersten Schritt, um zu beginnen!', 'info');

    </script>

</body>
</html>
