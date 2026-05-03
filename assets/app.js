// ── Global state (declared first) ────────────────────────────────
let darkMode = false;
let chatHistory = [];
let selectedCountry = null;

// ── Apply dark mode immediately from localStorage ─────────────────
(function () {
    if (localStorage.getItem('vf_dark') === '1') {
        document.documentElement.classList.add('dark-early');
        darkMode = true;
    }
})();

// ── Countries list (195) ──────────────────────────────────────────
const COUNTRIES = [
  {code:'af',name:'Afghanistan'},{code:'al',name:'Albania'},{code:'dz',name:'Algeria'},
  {code:'ad',name:'Andorra'},{code:'ao',name:'Angola'},{code:'ag',name:'Antigua and Barbuda'},
  {code:'ar',name:'Argentina'},{code:'am',name:'Armenia'},{code:'au',name:'Australia'},
  {code:'at',name:'Austria'},{code:'az',name:'Azerbaijan'},{code:'bs',name:'Bahamas'},
  {code:'bh',name:'Bahrain'},{code:'bd',name:'Bangladesh'},{code:'bb',name:'Barbados'},
  {code:'by',name:'Belarus'},{code:'be',name:'Belgium'},{code:'bz',name:'Belize'},
  {code:'bj',name:'Benin'},{code:'bt',name:'Bhutan'},{code:'bo',name:'Bolivia'},
  {code:'ba',name:'Bosnia and Herzegovina'},{code:'bw',name:'Botswana'},{code:'br',name:'Brazil'},
  {code:'bn',name:'Brunei'},{code:'bg',name:'Bulgaria'},{code:'bf',name:'Burkina Faso'},
  {code:'bi',name:'Burundi'},{code:'cv',name:'Cabo Verde'},{code:'kh',name:'Cambodia'},
  {code:'cm',name:'Cameroon'},{code:'ca',name:'Canada'},{code:'cf',name:'Central African Republic'},
  {code:'td',name:'Chad'},{code:'cl',name:'Chile'},{code:'cn',name:'China'},
  {code:'co',name:'Colombia'},{code:'km',name:'Comoros'},{code:'cd',name:'Congo (DRC)'},
  {code:'cg',name:'Congo (Republic)'},{code:'cr',name:'Costa Rica'},{code:'hr',name:'Croatia'},
  {code:'cu',name:'Cuba'},{code:'cy',name:'Cyprus'},{code:'cz',name:'Czech Republic'},
  {code:'dk',name:'Denmark'},{code:'dj',name:'Djibouti'},{code:'dm',name:'Dominica'},
  {code:'do',name:'Dominican Republic'},{code:'ec',name:'Ecuador'},{code:'eg',name:'Egypt'},
  {code:'sv',name:'El Salvador'},{code:'gq',name:'Equatorial Guinea'},{code:'er',name:'Eritrea'},
  {code:'ee',name:'Estonia'},{code:'sz',name:'Eswatini'},{code:'et',name:'Ethiopia'},
  {code:'fj',name:'Fiji'},{code:'fi',name:'Finland'},{code:'fr',name:'France'},
  {code:'ga',name:'Gabon'},{code:'gm',name:'Gambia'},{code:'ge',name:'Georgia'},
  {code:'de',name:'Germany'},{code:'gh',name:'Ghana'},{code:'gr',name:'Greece'},
  {code:'gd',name:'Grenada'},{code:'gt',name:'Guatemala'},{code:'gn',name:'Guinea'},
  {code:'gw',name:'Guinea-Bissau'},{code:'gy',name:'Guyana'},{code:'ht',name:'Haiti'},
  {code:'hn',name:'Honduras'},{code:'hu',name:'Hungary'},{code:'is',name:'Iceland'},
  {code:'in',name:'India'},{code:'id',name:'Indonesia'},{code:'ir',name:'Iran'},
  {code:'iq',name:'Iraq'},{code:'ie',name:'Ireland'},{code:'il',name:'Israel'},
  {code:'it',name:'Italy'},{code:'jm',name:'Jamaica'},{code:'jp',name:'Japan'},
  {code:'jo',name:'Jordan'},{code:'kz',name:'Kazakhstan'},{code:'ke',name:'Kenya'},
  {code:'ki',name:'Kiribati'},{code:'kw',name:'Kuwait'},{code:'kg',name:'Kyrgyzstan'},
  {code:'la',name:'Laos'},{code:'lv',name:'Latvia'},{code:'lb',name:'Lebanon'},
  {code:'ls',name:'Lesotho'},{code:'lr',name:'Liberia'},{code:'ly',name:'Libya'},
  {code:'li',name:'Liechtenstein'},{code:'lt',name:'Lithuania'},{code:'lu',name:'Luxembourg'},
  {code:'mg',name:'Madagascar'},{code:'mw',name:'Malawi'},{code:'my',name:'Malaysia'},
  {code:'mv',name:'Maldives'},{code:'ml',name:'Mali'},{code:'mt',name:'Malta'},
  {code:'mh',name:'Marshall Islands'},{code:'mr',name:'Mauritania'},{code:'mu',name:'Mauritius'},
  {code:'mx',name:'Mexico'},{code:'fm',name:'Micronesia'},{code:'md',name:'Moldova'},
  {code:'mc',name:'Monaco'},{code:'mn',name:'Mongolia'},{code:'me',name:'Montenegro'},
  {code:'ma',name:'Morocco'},{code:'mz',name:'Mozambique'},{code:'mm',name:'Myanmar'},
  {code:'na',name:'Namibia'},{code:'nr',name:'Nauru'},{code:'np',name:'Nepal'},
  {code:'nl',name:'Netherlands'},{code:'nz',name:'New Zealand'},{code:'ni',name:'Nicaragua'},
  {code:'ne',name:'Niger'},{code:'ng',name:'Nigeria'},{code:'kp',name:'North Korea'},
  {code:'mk',name:'North Macedonia'},{code:'no',name:'Norway'},{code:'om',name:'Oman'},
  {code:'pk',name:'Pakistan'},{code:'pw',name:'Palau'},{code:'pa',name:'Panama'},
  {code:'pg',name:'Papua New Guinea'},{code:'py',name:'Paraguay'},{code:'pe',name:'Peru'},
  {code:'ph',name:'Philippines'},{code:'pl',name:'Poland'},{code:'pt',name:'Portugal'},
  {code:'qa',name:'Qatar'},{code:'ro',name:'Romania'},{code:'ru',name:'Russia'},
  {code:'rw',name:'Rwanda'},{code:'kn',name:'Saint Kitts and Nevis'},{code:'lc',name:'Saint Lucia'},
  {code:'vc',name:'Saint Vincent and the Grenadines'},{code:'ws',name:'Samoa'},{code:'sm',name:'San Marino'},
  {code:'st',name:'Sao Tome and Principe'},{code:'sa',name:'Saudi Arabia'},{code:'sn',name:'Senegal'},
  {code:'rs',name:'Serbia'},{code:'sc',name:'Seychelles'},{code:'sl',name:'Sierra Leone'},
  {code:'sg',name:'Singapore'},{code:'sk',name:'Slovakia'},{code:'si',name:'Slovenia'},
  {code:'sb',name:'Solomon Islands'},{code:'so',name:'Somalia'},{code:'za',name:'South Africa'},
  {code:'ss',name:'South Sudan'},{code:'es',name:'Spain'},{code:'lk',name:'Sri Lanka'},
  {code:'sd',name:'Sudan'},{code:'sr',name:'Suriname'},{code:'se',name:'Sweden'},
  {code:'ch',name:'Switzerland'},{code:'sy',name:'Syria'},{code:'tw',name:'Taiwan'},
  {code:'tj',name:'Tajikistan'},{code:'tz',name:'Tanzania'},{code:'th',name:'Thailand'},
  {code:'tl',name:'Timor-Leste'},{code:'tg',name:'Togo'},{code:'to',name:'Tonga'},
  {code:'tt',name:'Trinidad and Tobago'},{code:'tn',name:'Tunisia'},{code:'tr',name:'Turkey'},
  {code:'tm',name:'Turkmenistan'},{code:'tv',name:'Tuvalu'},{code:'ug',name:'Uganda'},
  {code:'ua',name:'Ukraine'},{code:'ae',name:'United Arab Emirates'},{code:'gb',name:'United Kingdom'},
  {code:'us',name:'United States'},{code:'uy',name:'Uruguay'},{code:'uz',name:'Uzbekistan'},
  {code:'vu',name:'Vanuatu'},{code:'va',name:'Vatican City'},{code:'ve',name:'Venezuela'},
  {code:'vn',name:'Vietnam'},{code:'ye',name:'Yemen'},{code:'zm',name:'Zambia'},
  {code:'zw',name:'Zimbabwe'}
];

function flagImg(code) {
    return `<img src="https://flagcdn.com/20x15/${code}.png" width="20" height="15" alt="" style="border-radius:2px;flex-shrink:0">`;
}

function flagImgPremium(code) {
    return `<img src="https://flagcdn.com/w80/${code}.png" class="country-flag-premium" alt="">`;
}

// ── Country dropdown ──────────────────────────────────────────────
function buildCountryList(filter = '') {
    // This is now redundant but kept as an alias for buildCompulsoryCountryList
    buildCompulsoryCountryList(filter);
}

function selectCountry(c) {
    selectedCountry = c.name;
    const flagEl = document.getElementById('selectedFlag');
    const nameEl = document.getElementById('selectedName');
    
    if (flagEl) flagEl.innerHTML = flagImg(c.code);
    if (nameEl) nameEl.textContent = c.name;
    
    closeCountryDropdown();
    saveRegion();
    
    // If we have a pending message, send it now
    if (window.pendingChatText) {
        const textToSend = window.pendingChatText;
        window.pendingChatText = null;
        // Small delay to let the UI update the country flag
        setTimeout(() => {
            sendMessage(textToSend);
        }, 100);
    }
}

function toggleCountryDropdown() {
    const wrap = document.getElementById('countryTrigger')?.parentElement;
    if (!wrap) return;
    const isOpen = wrap.classList.contains('open');
    if (isOpen) { closeCountryDropdown(); return; }
    wrap.classList.add('open');
    buildCountryList();
    setTimeout(() => document.getElementById('countrySearch')?.focus(), 50);
}

function closeCountryDropdown() {
    const wrap = document.getElementById('countryTrigger')?.parentElement;
    if (wrap) wrap.classList.remove('open');
}

function filterCountries() {
    buildCountryList(document.getElementById('countrySearch').value);
}

document.addEventListener('click', e => {
    const wrap = document.getElementById('countryTrigger')?.parentElement;
    if (wrap && !wrap.contains(e.target)) closeCountryDropdown();
});

// ── Compulsory Country Dropdown ───────────────────────────────────
function buildCompulsoryCountryList(filter = '') {
    const ul = document.getElementById('compulsoryCountryList');
    if (!ul) return;
    const q = filter.toLowerCase();
    ul.innerHTML = '';
    
    const filtered = COUNTRIES.filter(c => c.name.toLowerCase().includes(q));
    
    if (filtered.length === 0) {
        ul.innerHTML = `<div class="premium-country-item" style="justify-content:center; opacity:0.5; pointer-events:none;">No countries found</div>`;
        return;
    }

    filtered.forEach(c => {
        const item = document.createElement('div');
        item.className = 'premium-country-item';
        if (selectedCountry === c.name) item.classList.add('selected');
        item.innerHTML = `
            ${flagImgPremium(c.code)}
            <span class="country-name-premium">${c.name}</span>
            <span class="material-icons-round" style="font-size:1.2rem; color:#3b82f6; opacity:${selectedCountry === c.name ? 1 : 0}">check_circle</span>
        `;
        item.onclick = () => selectCountry(c);
        ul.appendChild(item);
    });
}

function filterCompulsoryCountries() {
    buildCompulsoryCountryList(document.getElementById('compulsoryCountrySearch').value);
}

// ── Mobile Sidebar Toggle ─────────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
}

// ── Dark mode ─────────────────────────────────────────────────────
function toggleDarkMode() {
    darkMode = !darkMode;
    document.body.classList.toggle('dark', darkMode);
    localStorage.setItem('vf_dark', darkMode ? '1' : '0');
    const btn = document.getElementById('darkBtn');
    if (btn) btn.querySelector('.material-icons-round').textContent = darkMode ? 'light_mode' : 'dark_mode';
}

// ── Clear history modal ───────────────────────────────────────────────
function clearHistory() {
    document.getElementById('clearModal').classList.add('active');
}
function closeModal() {
    document.getElementById('clearModal').classList.remove('active');
}
async function confirmClearHistory() {
    closeModal();
    try { await fetch('api/history.php', { method: 'DELETE' }); } catch(e) {}
    chatHistory = [];
    const container = document.getElementById('chatMessages');
    if (container) {
        container.innerHTML = `
        <div class="message assistant">
            <div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div>
            <div class="bubble">Chat history cleared. How can I help you today?</div>
        </div>`;
    }
}
// Close modal on overlay click
document.addEventListener('click', e => {
    const modal = document.getElementById('clearModal');
    if (modal && e.target === modal) closeModal();
});

// ── View Switcher ─────────────────────────────────────────────────
function switchView(viewId, btn) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.snav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('view-' + viewId).classList.add('active');
    if (btn) btn.classList.add('active');
    if (window.innerWidth <= 768) toggleSidebar(); // Close sidebar on mobile
}

// ── Side Panels ───────────────────────────────────────────────────
function openPanel(id) {
    document.getElementById(id).classList.add('open');
    document.getElementById('panelOverlay').classList.add('active');
}
function closePanel(id) {
    document.getElementById(id).classList.remove('open');
    document.getElementById('panelOverlay').classList.remove('active');
}
function closeAllPanels() {
    document.querySelectorAll('.side-panel').forEach(p => p.classList.remove('open'));
    document.getElementById('panelOverlay').classList.remove('active');
}
function markNotifsRead() {
    // dummy function
    const badge = document.querySelector('.notif-badge');
    if (badge) badge.remove();
    document.querySelectorAll('.snav-badge').forEach(b => b.remove());
    closeAllPanels();
}
function exportChat() {
    alert("Exporting chat...");
}

// ── Password visibility toggle ────────────────────────────────────
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('.material-icons-round').textContent = isHidden ? 'visibility_off' : 'visibility';
}

// ── Auth tab switcher ─────────────────────────────────────────────
function switchTab(tab) {
    document.getElementById('loginForm').style.display  = tab === 'login'  ? 'flex' : 'none';
    document.getElementById('signupForm').style.display = tab === 'signup' ? 'flex' : 'none';
    document.querySelectorAll('.tab-btn').forEach((btn, i) => {
        btn.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'signup'));
    });
    document.getElementById('cardTitle').textContent    = tab === 'login' ? 'Welcome back' : 'Create an account';
    document.getElementById('cardSubtitle').textContent = tab === 'login' ? 'Sign in to your VoterFlow account' : 'Join VoterFlow and start your civic journey';
}

// Auth error from URL
const params = new URLSearchParams(window.location.search);
const errorMap = {
    invalid_input:       'Please fill in all fields correctly.',
    email_exists:        'An account with this email already exists.',
    invalid_credentials: 'Incorrect email or password.',
    server_error:        'A server error occurred. Please try again.',
    oauth_failed:        'Google sign-in failed. Please try again.'
};
if (params.get('error') && errorMap[params.get('error')]) {
    const target = window.location.hash === '#signup' ? 'signupError' : 'loginError';
    const el = document.getElementById(target);
    if (el) { el.textContent = errorMap[params.get('error')]; el.style.display = 'block'; }
    if (window.location.hash === '#signup') switchTab('signup');
}

// ── Chat ──────────────────────────────────────────────────────────
function appendMessage(role, text) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = `message ${role}`;
    if (role === 'assistant') {
        div.innerHTML = `<div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div><div class="bubble">${formatText(text)}</div>`;
    } else {
        div.innerHTML = `<div class="bubble">${formatText(text)}</div>`;
    }
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function appendMessageAnimated(role, text) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = `message ${role}`;
    if (role === 'assistant') {
        div.innerHTML = `<div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div><div class="bubble"></div>`;
    } else {
        div.innerHTML = `<div class="bubble"></div>`;
    }
    container.appendChild(div);
    
    const bubble = div.querySelector('.bubble');
    let index = 0;
    let currentText = '';
    
    function typeNext() {
        if (index < text.length) {
            currentText += text.charAt(index);
            bubble.innerHTML = formatText(currentText);
            // Ensure scroll stays at bottom while typing
            if (container.scrollHeight - container.scrollTop < container.clientHeight + 100) {
                container.scrollTop = container.scrollHeight;
            }
            index++;
            // slightly randomize typing speed for realism (10ms - 25ms)
            setTimeout(typeNext, Math.random() * 15 + 10);
        }
    }
    typeNext();
}

function formatText(text) {
    return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function setLoading(on) {
    const btn = document.getElementById('sendBtn');
    if (btn) btn.disabled = on;
}

async function sendMessage(textParam = null) {
    const input = document.getElementById('userInput');
    const text  = textParam || input.value.trim();
    if (!text) return;

    // If no country selected, ask in-chat and save the message
    if (!selectedCountry) {
        window.pendingChatText = text;
        
        // Only append the user message if it came from the input box
        if (!textParam) {
            appendMessage('user', text);
            chatHistory.push({ role: 'user', content: text });
            input.value = '';
            input.style.height = 'auto';
        }
        
        appendMessageAnimated('assistant', "I'd love to help! Before we start, could you please select your country in the sidebar? This helps me provide the most accurate election details for your region.");
        return;
    }

    // Only append if it's a new message
    if (!textParam) {
        appendMessage('user', text);
        chatHistory.push({ role: 'user', content: text });
        input.value = '';
        input.style.height = 'auto';
    }
    setLoading(true);

    const typingDiv = document.createElement('div');
    typingDiv.className = 'message assistant';
    typingDiv.innerHTML = `<div class="msg-avatar"><span class="material-icons-round">smart_toy</span></div><div class="bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>`;
    document.getElementById('chatMessages').appendChild(typingDiv);
    document.getElementById('chatMessages').scrollTop = 99999;

    try {
        const res  = await fetch('api/chat.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: text, history: chatHistory.slice(-10) })
        });
        const data = await res.json();
        typingDiv.remove();
        const reply = data.reply || 'Sorry, something went wrong.';
        appendMessageAnimated('assistant', reply);
        chatHistory.push({ role: 'assistant', content: reply });
    } catch {
        typingDiv.remove();
        appendMessageAnimated('assistant', 'Network error. Please check your connection.');
    }
    setLoading(false);
}

function sendQuick(text) {
    document.getElementById('userInput').value = text;
    sendMessage();
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

// ── Save region ───────────────────────────────────────────────────
async function saveRegion() {
    const val = selectedCountry || document.getElementById('selectedName')?.textContent.trim();
    if (!val || val === 'Select your country') return;
    await fetch('api/milestones.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ state_province: val })
    });
    const chip = document.querySelector('.region-chip');
    if (chip) {
        const match = COUNTRIES.find(c => c.name === val);
        const flagHtml = match ? flagImg(match.code) : '<span class="material-icons-round">public</span>';
        chip.innerHTML = `${flagHtml} ${val}`;
        chip.style.display = '';
    }
}

// ── Mark milestone ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.milestone-item:not(.done)').forEach(el => {
        el.addEventListener('click', async () => {
            await fetch('api/milestones.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ milestone: el.dataset.milestone })
            });
            el.classList.add('done');
            el.querySelector('.milestone-icon').textContent = 'task_alt';
            el.querySelector('.milestone-hint').textContent = 'Completed';
            el.querySelector('.milestone-icon').style.color = '#34d399';
            el.querySelector('.milestone-hint').style.color = '#34d399';
            el.querySelector('.milestone-hint').style.opacity = '.8';
            const done  = document.querySelectorAll('.milestone-item.done').length;
            const pct   = Math.round((done / 3) * 100);
            const fill  = document.querySelector('.progress-fill');
            const pctEl = document.querySelector('.progress-pct');
            if (fill)  fill.style.width = pct + '%';
            if (pctEl) pctEl.textContent = pct + '%';
        });
    });

    // Country dropdown init
    const saved = document.getElementById('selectedName')?.textContent.trim();
    if (saved && saved !== 'Select your country') {
        const match = COUNTRIES.find(c => c.name === saved);
        if (match) {
            selectedCountry = match.name;
            document.getElementById('selectedFlag').innerHTML = flagImg(match.code);
            // Update navbar chip flag
            const chip = document.querySelector('.region-chip');
            if (chip) chip.innerHTML = `${flagImg(match.code)} ${match.name}`;
        }
    } else {
        // We no longer show the modal automatically on load.
        // It will trigger when the user tries to chat.
    }
    buildCountryList();

    // Textarea auto-resize
    const ta = document.getElementById('userInput');
    if (ta) ta.addEventListener('input', () => {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
    });

    // Init chatHistory from rendered messages
    document.querySelectorAll('#chatMessages .message').forEach(el => {
        const role   = el.classList.contains('user') ? 'user' : 'assistant';
        const bubble = el.querySelector('.bubble');
        if (bubble) chatHistory.push({ role, content: bubble.innerText.trim() });
    });

    // Dark mode sync
    const stored = localStorage.getItem('vf_dark');
    if (stored !== null) darkMode = stored === '1';
    document.body.classList.toggle('dark', darkMode);
    const btn = document.getElementById('darkBtn');
    if (btn) btn.querySelector('.material-icons-round').textContent = darkMode ? 'light_mode' : 'dark_mode';

    // Scroll chat to bottom
    const cm = document.getElementById('chatMessages');
    if (cm) cm.scrollTop = cm.scrollHeight;

    // Register service worker
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/VoterFlow/sw.js');
});
