<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Змейка</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #1a1a2e;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100dvh;
            font-family: Arial, sans-serif;
            overflow: hidden;
            touch-action: none;
        }
        #header {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 400px;
            padding: 0 8px 10px;
            color: #fff;
        }
        .stat { text-align: center; }
        .stat-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 26px; font-weight: bold; color: #00e676; }
        #best-value { color: #ffd740; }
        canvas { border-radius: 12px; display: block; }

        #overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            background: rgba(0,0,0,0.88);
            border-radius: 12px;
            padding: 24px 20px;
            color: #fff;
            text-align: center;
        }
        #overlay h1 { font-size: 26px; }
        #overlay .sub { font-size: 14px; color: #aaa; }

        #food-preview {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: #ff5252;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            overflow: hidden;
            border: 3px solid #444;
            flex-shrink: 0;
        }
        #food-preview img {
            width: 100%; height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .upload-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #0f3460;
            border: 2px dashed #00e676;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            color: #00e676;
            transition: background 0.2s;
        }
        .upload-label:active { background: #1a4a7a; }

        #file-input { display: none; }

        #upload-status { font-size: 13px; color: #888; min-height: 18px; }

        .btn-green {
            padding: 13px 32px;
            background: #00e676;
            color: #1a1a2e;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            max-width: 240px;
        }
        .btn-green:active { transform: scale(0.97); }
        .btn-green:disabled { background: #444; color: #888; cursor: default; }

        .btn-skip {
            background: none;
            border: none;
            color: #555;
            font-size: 13px;
            cursor: pointer;
            text-decoration: underline;
            padding: 4px;
        }
        .btn-skip:active { color: #888; }

        #swipe-hint {
            position: absolute;
            bottom: 16px;
            color: #333;
            font-size: 12px;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div id="header">
    <div class="stat">
        <div class="stat-label">Счёт</div>
        <div class="stat-value" id="score-val">0</div>
    </div>
    <div class="stat">
        <div class="stat-label">Рекорд</div>
        <div class="stat-value" id="best-value">0</div>
    </div>
</div>

<div style="position:relative; display:inline-block;">
    <canvas id="c"></canvas>

    <div id="overlay">
        <h1>🐍 Змейка</h1>
        <div class="sub">Выбери фото для еды или пропусти</div>

        <div id="food-preview">🍎</div>

        <label class="upload-label" for="file-input">
            📷 Выбрать фото
        </label>
        <input type="file" id="file-input" accept="image/*">

        <div id="upload-status"></div>

        <button class="btn-green" id="btn-start" onclick="handleStart()">Начать игру</button>
        <button class="btn-skip" onclick="skipPhoto()">Играть без фото</button>
    </div>
</div>

<div id="swipe-hint">Свайп для управления</div>

<script>
const canvas  = document.getElementById('c');
const ctx     = canvas.getContext('2d');

const SIZE = Math.min(400, window.innerWidth - 16);
const COLS = 10;
const CELL = Math.floor(SIZE / COLS);
const W    = COLS * CELL;
canvas.width = canvas.height = W;

const overlay    = document.getElementById('overlay');
const scoreEl    = document.getElementById('score-val');
const bestEl     = document.getElementById('best-value');
const statusEl   = document.getElementById('upload-status');
const previewEl  = document.getElementById('food-preview');
const fileInput  = document.getElementById('file-input');
const btnStart   = document.getElementById('btn-start');

let snake, dir, nextDir, food, score, best = 0, running, interval, speed;
let foodImage = null;
let selectedFile = null;
let foodPulse = 0;

// File selected — show preview
fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    selectedFile = file;
    const url = URL.createObjectURL(file);
    previewEl.innerHTML = `<img src="${url}" alt="еда">`;
    statusEl.textContent = '✓ Фото выбрано';
    statusEl.style.color = '#00e676';
});

async function handleStart() {
    if (selectedFile) {
        btnStart.disabled = true;
        statusEl.textContent = 'Загружаю...';
        statusEl.style.color = '#aaa';

        const formData = new FormData();
        formData.append('photo', selectedFile);

        try {
            const resp = await fetch('upload.php', { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                foodImage = new Image();
                foodImage.src = data.url;
                await new Promise(res => { foodImage.onload = res; foodImage.onerror = res; });
                statusEl.textContent = '';
            } else {
                statusEl.textContent = data.error || 'Ошибка загрузки';
                statusEl.style.color = '#ff5252';
                btnStart.disabled = false;
                return;
            }
        } catch {
            statusEl.textContent = 'Ошибка сети — играем без фото';
            statusEl.style.color = '#ff5252';
            foodImage = null;
        }
    }
    startGame();
}

function skipPhoto() {
    foodImage = null;
    startGame();
}

function rand(max) { return Math.floor(Math.random() * max); }

function placeFood() {
    let pos;
    do { pos = { x: rand(COLS), y: rand(COLS) }; }
    while (snake.some(s => s.x === pos.x && s.y === pos.y));
    food = pos;
}

function startGame() {
    snake   = [{ x: 5, y: 5 }, { x: 4, y: 5 }, { x: 3, y: 5 }];
    dir     = { x: 1, y: 0 };
    nextDir = { x: 1, y: 0 };
    score   = 0; speed = 130;
    scoreEl.textContent = 0;
    overlay.style.display = 'none';
    placeFood();
    running = true;
    clearInterval(interval);
    interval = setInterval(tick, speed);
}

function setSpeed(s) {
    clearInterval(interval);
    speed = s;
    interval = setInterval(tick, speed);
}

function tick() {
    dir = { ...nextDir };
    const head = { x: snake[0].x + dir.x, y: snake[0].y + dir.y };
    if (head.x < 0 || head.x >= COLS || head.y < 0 || head.y >= COLS) { gameOver(); return; }
    if (snake.some(s => s.x === head.x && s.y === head.y))             { gameOver(); return; }
    snake.unshift(head);
    if (head.x === food.x && head.y === food.y) {
        score++;
        scoreEl.textContent = score;
        if (score > best) { best = score; bestEl.textContent = best; }
        placeFood();
        if (score % 5 === 0 && speed > 60) setSpeed(speed - 8);
    } else {
        snake.pop();
    }
    draw();
}

function gameOver() {
    clearInterval(interval);
    running = false;
    overlay.innerHTML = `
        <h1>Игра окончена!</h1>
        <div style="font-size:42px;font-weight:bold;color:#00e676">${score}</div>
        <div style="font-size:16px;color:#ffd740">Рекорд: ${best}</div>
        <button class="btn-green" onclick="resetToStart()" style="margin-top:6px">Играть снова</button>
    `;
    overlay.style.display = 'flex';
}

function resetToStart() {
    selectedFile = null;
    overlay.innerHTML = `
        <h1>🐍 Змейка</h1>
        <div class="sub">Выбери фото для еды или пропусти</div>
        <div id="food-preview">${foodImage ? `<img src="${foodImage.src}">` : '🍎'}</div>
        <label class="upload-label" for="file-input">📷 Выбрать фото</label>
        <input type="file" id="file-input" accept="image/*">
        <div id="upload-status"></div>
        <button class="btn-green" id="btn-start" onclick="handleStart()">Начать игру</button>
        <button class="btn-skip" onclick="skipPhoto()">Играть без фото</button>
    `;
    overlay.style.display = 'flex';
    document.getElementById('file-input').addEventListener('change', () => {
        const file = document.getElementById('file-input').files[0];
        if (!file) return;
        selectedFile = file;
        const url = URL.createObjectURL(file);
        document.getElementById('food-preview').innerHTML = `<img src="${url}">`;
        document.getElementById('upload-status').textContent = '✓ Фото выбрано';
        document.getElementById('upload-status').style.color = '#00e676';
    });
}

/* ── Drawing ── */

function drawRoundRect(x, y, w, h, r) {
    ctx.beginPath(); ctx.roundRect(x, y, w, h, r); ctx.fill();
}

function draw() {
    ctx.fillStyle = '#16213e';
    ctx.fillRect(0, 0, W, W);

    ctx.strokeStyle = '#0f3460';
    ctx.lineWidth = 0.5;
    for (let i = 0; i <= COLS; i++) {
        ctx.beginPath(); ctx.moveTo(i * CELL, 0); ctx.lineTo(i * CELL, W); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(0, i * CELL); ctx.lineTo(W, i * CELL); ctx.stroke();
    }

    // Food
    const fx = food.x * CELL;
    const fy = food.y * CELL;
    foodPulse += 0.12;

    if (foodImage && foodImage.complete && foodImage.naturalWidth > 0) {
        const pad = 2;
        ctx.save();
        ctx.beginPath();
        ctx.arc(fx + CELL / 2, fy + CELL / 2, CELL / 2 - pad, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(foodImage, fx + pad, fy + pad, CELL - pad * 2, CELL - pad * 2);
        ctx.restore();
        // Glow ring
        const glow = 6 + Math.sin(foodPulse) * 3;
        ctx.shadowColor = '#ff9800';
        ctx.shadowBlur  = glow;
        ctx.strokeStyle = '#ff9800';
        ctx.lineWidth   = 2;
        ctx.beginPath();
        ctx.arc(fx + CELL / 2, fy + CELL / 2, CELL / 2 - pad, 0, Math.PI * 2);
        ctx.stroke();
        ctx.shadowBlur = 0;
    } else {
        const glow = 8 + Math.sin(foodPulse) * 4;
        ctx.shadowColor = '#ff5252';
        ctx.shadowBlur  = glow;
        ctx.fillStyle   = '#ff5252';
        ctx.beginPath();
        ctx.arc(fx + CELL / 2, fy + CELL / 2, CELL * 0.38, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;
        ctx.strokeStyle = '#ff8a65';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(fx + CELL / 2, fy + CELL * 0.12);
        ctx.lineTo(fx + CELL / 2 + 3, fy + CELL * 0.02);
        ctx.stroke();
    }

    ctx.shadowBlur = 0;

    // Snake
    for (let i = snake.length - 1; i >= 0; i--) {
        const s = snake[i];
        const px = s.x * CELL + 1, py = s.y * CELL + 1, sz = CELL - 2;

        if (i === 0) {
            ctx.shadowColor = '#00e676';
            ctx.shadowBlur  = 10;
            ctx.fillStyle   = '#00e676';
            drawRoundRect(px, py, sz, sz, 5);
            ctx.shadowBlur = 0;

            const ex  = dir.x === 1 ? 0.65 : dir.x === -1 ? 0.2 : 0.3;
            const ey  = dir.y === 1 ? 0.65 : dir.y === -1 ? 0.2 : 0.3;
            const ex2 = dir.x !== 0 ? ex : 0.7;
            const ey2 = dir.y !== 0 ? ey : 0.3;

            ctx.fillStyle = '#fff';
            ctx.beginPath(); ctx.arc(px + sz * ex,  py + sz * ey,  3.5, 0, Math.PI * 2); ctx.fill();
            ctx.beginPath(); ctx.arc(px + sz * ex2, py + sz * ey2, 3.5, 0, Math.PI * 2); ctx.fill();
            ctx.fillStyle = '#1a1a2e';
            ctx.beginPath(); ctx.arc(px + sz * ex  + dir.x, py + sz * ey  + dir.y, 1.8, 0, Math.PI * 2); ctx.fill();
            ctx.beginPath(); ctx.arc(px + sz * ex2 + dir.x, py + sz * ey2 + dir.y, 1.8, 0, Math.PI * 2); ctx.fill();
        } else {
            const t = i / snake.length;
            ctx.fillStyle = `rgb(0, ${Math.round(200 - t * 80)}, ${Math.round(80 + t * 30)})`;
            drawRoundRect(px + 1, py + 1, sz - 2, sz - 2, 4);
        }
    }
}

/* ── Controls ── */

const KEY_MAP = {
    ArrowUp:    { x: 0, y: -1 }, ArrowDown:  { x: 0, y:  1 },
    ArrowLeft:  { x: -1, y: 0 }, ArrowRight: { x: 1, y:  0 },
    w: { x: 0, y: -1 }, W: { x: 0, y: -1 },
    s: { x: 0, y:  1 }, S: { x: 0, y:  1 },
    a: { x: -1, y: 0 }, A: { x: -1, y: 0 },
    d: { x: 1,  y: 0 }, D: { x: 1,  y: 0 },
};
document.addEventListener('keydown', e => {
    const d = KEY_MAP[e.key];
    if (d && running && !(d.x === -dir.x && d.y === -dir.y)) { nextDir = d; e.preventDefault(); }
});

let touchStart = null;
canvas.addEventListener('touchstart', e => {
    touchStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
}, { passive: true });
canvas.addEventListener('touchend', e => {
    if (!touchStart || !running) return;
    const dx = e.changedTouches[0].clientX - touchStart.x;
    const dy = e.changedTouches[0].clientY - touchStart.y;
    if (Math.abs(dx) < 10 && Math.abs(dy) < 10) return;
    let d = Math.abs(dx) > Math.abs(dy)
        ? (dx > 0 ? { x: 1, y: 0 } : { x: -1, y: 0 })
        : (dy > 0 ? { x: 0, y: 1 } : { x: 0, y: -1 });
    if (!(d.x === -dir.x && d.y === -dir.y)) nextDir = d;
    touchStart = null;
}, { passive: true });

draw();
</script>
</body>
</html>
