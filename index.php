<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Сапёр</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            background: #1a1a2e;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 12px;
            user-select: none;
        }

        /* ── Header ── */
        #header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 420px;
            background: #16213e;
            border-radius: 14px;
            padding: 10px 18px;
            margin-bottom: 10px;
            border: 2px solid #0f3460;
        }
        .hbox {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 70px;
        }
        .hbox.right { justify-content: flex-end; }
        .hval {
            font-size: 22px;
            font-weight: bold;
            color: #ff5252;
            font-variant-numeric: tabular-nums;
            min-width: 36px;
            text-align: center;
        }
        .hval.timer { color: #00e676; }
        .hicon { font-size: 20px; }

        #smile {
            font-size: 30px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0 4px;
            line-height: 1;
        }
        #smile:active { transform: scale(0.9); }

        /* ── Difficulty ── */
        #diff-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .diff-btn {
            padding: 7px 16px;
            border-radius: 20px;
            border: 2px solid #0f3460;
            background: #16213e;
            color: #aaa;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .diff-btn.active {
            background: #0f3460;
            color: #fff;
            border-color: #00e676;
        }
        .diff-btn:active { transform: scale(0.95); }

        /* ── Board ── */
        #board-wrap {
            position: relative;
        }
        #board {
            display: grid;
            gap: 2px;
            background: #0f3460;
            border-radius: 10px;
            padding: 6px;
            border: 2px solid #0f3460;
        }
        .cell {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.08s;
            position: relative;
            overflow: hidden;
        }
        .cell.hidden {
            background: #1e3a5f;
            border-bottom: 3px solid #0a2040;
            border-right: 3px solid #0a2040;
            border-top: 2px solid #2a5080;
            border-left: 2px solid #2a5080;
        }
        .cell.hidden:active { background: #2a4a6f; }
        .cell.revealed {
            background: #16213e;
            border: 1px solid #0f2a45;
            cursor: default;
        }
        .cell.flagged { background: #1e3a5f; }
        .cell.mine-hit { background: #c0392b !important; }
        .cell.mine-shown { background: #16213e; }

        .n1 { color: #4fc3f7; }
        .n2 { color: #81c784; }
        .n3 { color: #ff7043; }
        .n4 { color: #7986cb; }
        .n5 { color: #ef5350; }
        .n6 { color: #26c6da; }
        .n7 { color: #ec407a; }
        .n8 { color: #bdbdbd; }

        /* ── Flag mode button ── */
        #flag-btn {
            margin-top: 10px;
            padding: 10px 28px;
            border-radius: 50px;
            border: 2px solid #0f3460;
            background: #16213e;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.15s;
        }
        #flag-btn.on {
            background: #7f1d1d;
            border-color: #ff5252;
            color: #ff5252;
        }
        #flag-btn:active { transform: scale(0.96); }

        /* ── Overlay ── */
        #overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.82);
            border-radius: 10px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #fff;
            text-align: center;
        }
        #overlay.show { display: flex; }
        #overlay h2 { font-size: 28px; }
        #overlay .sub { font-size: 15px; color: #aaa; }
        #overlay .big { font-size: 52px; }
        #restart-btn {
            padding: 12px 32px;
            background: #00e676;
            color: #1a1a2e;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 4px;
        }
        #restart-btn:active { transform: scale(0.97); }

        #hint { color: #444; font-size: 12px; margin-top: 8px; }
    </style>
</head>
<body>

<div id="header">
    <div class="hbox">
        <span class="hicon">💣</span>
        <span class="hval" id="mines-left">0</span>
    </div>
    <button id="smile" onclick="restartGame()">🙂</button>
    <div class="hbox right">
        <span class="hval timer" id="timer-val">0</span>
        <span class="hicon">⏱</span>
    </div>
</div>

<div id="diff-bar">
    <button class="diff-btn active" onclick="setDiff('easy',this)">Легко</button>
    <button class="diff-btn" onclick="setDiff('medium',this)">Средне</button>
    <button class="diff-btn" onclick="setDiff('hard',this)">Сложно</button>
</div>

<div id="board-wrap">
    <div id="board"></div>
    <div id="overlay">
        <div class="big" id="overlay-icon"></div>
        <h2 id="overlay-title"></h2>
        <div class="sub" id="overlay-sub"></div>
        <button id="restart-btn" onclick="restartGame()">Играть снова</button>
    </div>
</div>

<button id="flag-btn" onclick="toggleFlagMode()">🚩 Режим флага: выкл</button>
<div id="hint">Зажми ячейку чтобы поставить флаг</div>

<script>
const DIFF = {
    easy:   { rows: 8,  cols: 8,  mines: 10 },
    medium: { rows: 10, cols: 10, mines: 20 },
    hard:   { rows: 12, cols: 12, mines: 30 },
};

const NUM_COLORS = ['','n1','n2','n3','n4','n5','n6','n7','n8'];

let cfg, board, state, firstClick, minesLeft, elapsed, timerInterval, flagMode;
let currentDiff = 'easy';

function setDiff(d, btn) {
    currentDiff = d;
    document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    restartGame();
}

function restartGame() {
    cfg        = DIFF[currentDiff];
    board      = [];
    state      = 'wait';
    firstClick = true;
    flagMode   = false;
    elapsed    = 0;
    minesLeft  = cfg.mines;
    clearInterval(timerInterval);

    document.getElementById('smile').textContent      = '🙂';
    document.getElementById('mines-left').textContent = minesLeft;
    document.getElementById('timer-val').textContent  = '0';
    document.getElementById('flag-btn').classList.remove('on');
    document.getElementById('flag-btn').textContent = '🚩 Режим флага: выкл';
    document.getElementById('overlay').classList.remove('show');

    for (let r = 0; r < cfg.rows; r++) {
        board[r] = [];
        for (let c = 0; c < cfg.cols; c++) {
            board[r][c] = { mine: false, revealed: false, flagged: false, adj: 0 };
        }
    }
    renderBoard();
}

function placeMines(sr, sc) {
    let placed = 0;
    while (placed < cfg.mines) {
        const r = Math.floor(Math.random() * cfg.rows);
        const c = Math.floor(Math.random() * cfg.cols);
        if (!board[r][c].mine && !(Math.abs(r - sr) <= 1 && Math.abs(c - sc) <= 1)) {
            board[r][c].mine = true;
            placed++;
        }
    }
    for (let r = 0; r < cfg.rows; r++)
        for (let c = 0; c < cfg.cols; c++)
            if (!board[r][c].mine)
                board[r][c].adj = countAdj(r, c);
}

function countAdj(r, c) {
    let n = 0;
    for (let dr = -1; dr <= 1; dr++)
        for (let dc = -1; dc <= 1; dc++) {
            const nr = r+dr, nc = c+dc;
            if (nr >= 0 && nr < cfg.rows && nc >= 0 && nc < cfg.cols && board[nr][nc].mine) n++;
        }
    return n;
}

function reveal(r, c) {
    if (r < 0 || r >= cfg.rows || c < 0 || c >= cfg.cols) return;
    const cell = board[r][c];
    if (cell.revealed || cell.flagged) return;
    cell.revealed = true;
    if (cell.adj === 0 && !cell.mine)
        for (let dr = -1; dr <= 1; dr++)
            for (let dc = -1; dc <= 1; dc++)
                reveal(r+dr, c+dc);
}

function toggleFlag(r, c) {
    const cell = board[r][c];
    if (cell.revealed) return;
    cell.flagged = !cell.flagged;
    minesLeft += cell.flagged ? -1 : 1;
    document.getElementById('mines-left').textContent = minesLeft;
    updateCell(r, c);
}

function tap(r, c) {
    if (state === 'lost' || state === 'won') return;
    const cell = board[r][c];
    if (cell.revealed) return;

    if (flagMode) { toggleFlag(r, c); return; }
    if (cell.flagged) return;

    if (firstClick) {
        firstClick = false;
        state = 'play';
        placeMines(r, c);
        timerInterval = setInterval(() => {
            elapsed++;
            document.getElementById('timer-val').textContent = elapsed;
        }, 1000);
    }

    if (cell.mine) {
        cell.revealed = true;
        gameOver(r, c);
        return;
    }

    reveal(r, c);
    checkWin();
    renderBoard();
}

function checkWin() {
    const unrevealed = board.flat().filter(c => !c.revealed).length;
    if (unrevealed === cfg.mines) {
        state = 'won';
        clearInterval(timerInterval);
        document.getElementById('smile').textContent = '😎';
        showOverlay(true);
    }
}

function gameOver(br, bc) {
    state = 'lost';
    clearInterval(timerInterval);
    document.getElementById('smile').textContent = '😵';
    // Reveal all mines
    for (let r = 0; r < cfg.rows; r++)
        for (let c = 0; c < cfg.cols; c++)
            if (board[r][c].mine && !board[r][c].flagged)
                board[r][c].revealed = true;
    renderBoard();
    // Mark hit cell
    const el = document.querySelector(`[data-r="${br}"][data-c="${bc}"]`);
    if (el) el.classList.add('mine-hit');
    showOverlay(false);
}

function showOverlay(win) {
    const ov = document.getElementById('overlay');
    document.getElementById('overlay-icon').textContent  = win ? '🎉' : '💥';
    document.getElementById('overlay-title').textContent = win ? 'Победа!' : 'Подрыв!';
    document.getElementById('overlay-sub').textContent   = win
        ? `Время: ${elapsed} сек`
        : 'Ты наступил на мину!';
    ov.classList.add('show');
}

function toggleFlagMode() {
    flagMode = !flagMode;
    const btn = document.getElementById('flag-btn');
    btn.classList.toggle('on', flagMode);
    btn.textContent = flagMode ? '🚩 Режим флага: вкл' : '🚩 Режим флага: выкл';
}

/* ── Render ── */

function cellSize() {
    const maxW = Math.min(window.innerWidth - 32, 420);
    return Math.floor((maxW - 12 - (cfg.cols - 1) * 2) / cfg.cols);
}

function renderBoard() {
    const boardEl = document.getElementById('board');
    const sz = cellSize();
    boardEl.style.gridTemplateColumns = `repeat(${cfg.cols}, ${sz}px)`;
    boardEl.innerHTML = '';

    for (let r = 0; r < cfg.rows; r++) {
        for (let c = 0; c < cfg.cols; c++) {
            const el = document.createElement('div');
            el.className = 'cell';
            el.dataset.r = r;
            el.dataset.c = c;
            el.style.width  = sz + 'px';
            el.style.height = sz + 'px';
            el.style.fontSize = Math.max(10, sz * 0.45) + 'px';
            applyCell(el, board[r][c]);
            attachEvents(el, r, c);
            boardEl.appendChild(el);
        }
    }
}

function updateCell(r, c) {
    const el = document.querySelector(`[data-r="${r}"][data-c="${c}"]`);
    if (el) applyCell(el, board[r][c]);
}

function applyCell(el, cell) {
    el.className = 'cell';
    el.textContent = '';

    if (cell.flagged && !cell.revealed) {
        el.classList.add('flagged', 'hidden');
        el.textContent = '🚩';
    } else if (!cell.revealed) {
        el.classList.add('hidden');
    } else if (cell.mine) {
        el.classList.add('mine-shown');
        el.textContent = '💣';
    } else {
        el.classList.add('revealed');
        if (cell.adj > 0) {
            el.textContent = cell.adj;
            el.classList.add(NUM_COLORS[cell.adj]);
        }
    }
}

/* ── Touch events ── */

function attachEvents(el, r, c) {
    let holdTimer = null;

    el.addEventListener('touchstart', e => {
        e.preventDefault();
        holdTimer = setTimeout(() => {
            holdTimer = null;
            toggleFlag(r, c);
        }, 400);
    }, { passive: false });

    el.addEventListener('touchend', e => {
        e.preventDefault();
        if (holdTimer !== null) {
            clearTimeout(holdTimer);
            holdTimer = null;
            tap(r, c);
        }
    }, { passive: false });

    el.addEventListener('touchmove', () => {
        if (holdTimer !== null) { clearTimeout(holdTimer); holdTimer = null; }
    }, { passive: true });

    el.addEventListener('click', () => tap(r, c));
    el.addEventListener('contextmenu', e => { e.preventDefault(); toggleFlag(r, c); });
}

restartGame();
</script>
</body>
</html>
