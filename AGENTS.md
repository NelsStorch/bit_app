# AGENTS.md

## Project Overview
This project is a collection of interactive network simulation tools and games designed for educational purposes. The application helps users understand concepts such as DNS, TCP/IP, Routing, and Network Planning.

### Key Files
- `public/index.html`: The main landing page.
- `public/netzwerk.php`: A step-by-step visualization of a web request (DNS -> TCP -> TLS -> HTTP).
- `public/plan.php`: A simple network diagram builder using HTML5 Canvas.
- `public/planpro.php`: An advanced version of the diagram builder with IP routing logic and automatic gateway configuration.
- `public/router.php`: A game where the user acts as a router, directing packets to the correct ports or gateway.
- `database/router_game.sql`: SQL initialization script for the database.
- `docs/README_DOCKER.md`: Instructions for running with Docker.

## Technology Stack
- **Frontend**: HTML5, JavaScript (Vanilla), Tailwind CSS.
- **Libraries**:
    - `particles.js`: For background effects on the landing page.
    - `Tone.js`: For audio synthesis in `router.php`.
    - `FontAwesome`: For icons.

## Development Guidelines

### Documentation
- All code must be written in **German**.
- Extensive documentation is required.
    - **PHP/HTML Files**: Use top-level comments to describe the file's purpose.
    - **JavaScript**: Use JSDoc-style comments for functions, especially those handling complex logic like pathfinding or animations.

### Styling
- Use **Tailwind CSS** for styling components.
- Maintain a consistent look and feel across all tools (e.g., using specific color palettes like the "Stadt Zürich" blue where applicable).
- Ensure headers and navigation are consistent (e.g., "Home" buttons on all tool pages).

### Code Quality
- Keep console logs to a minimum in production code.
- Ensure variable naming is clear and consistent.
- JavaScript logic is currently embedded within the PHP/HTML files for simplicity and portability. Maintain this structure unless a significant refactor is requested.

### Verification
- Always verify changes by checking the UI elements and ensuring no console errors are introduced.
- Test responsive design on standard screen sizes.
