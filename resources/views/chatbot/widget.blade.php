@auth
<div id="chatbot-widget" class="chatbot-widget">
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Abrir asistente" title="Asistente Los Robles">
        <i class="bi bi-robot"></i>
    </button>
    <div id="chatbot-panel" class="chatbot-panel d-none">
        <div class="chatbot-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-robot"></i>
                <span>Asistente Los Robles</span>
            </div>
            <button id="chatbot-close" class="chatbot-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="chatbot-messages" class="chatbot-messages">
            <div class="chatbot-message bot">
                <div class="chatbot-bubble">Hola, soy tu asistente virtual. ¿En qué puedo ayudarte hoy?</div>
                <div class="chatbot-time">Ahora</div>
            </div>
        </div>
        <div id="chatbot-typing" class="chatbot-typing d-none">
            <span></span><span></span><span></span>
        </div>
        <form id="chatbot-form" class="chatbot-form">
            @csrf
            <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Escribe tu pregunta..." autocomplete="off" maxlength="500">
            <button type="submit" class="chatbot-send" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>
</div>

@push('styles')
<style>
.chatbot-widget { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 1050; font-family: var(--bs-body-font-family); }
.chatbot-toggle { width: 3.5rem; height: 3.5rem; border-radius: 50%; border: none; background: var(--bs-primary); color: #fff; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,.2); cursor: pointer; transition: transform .2s, background .2s; display: flex; align-items: center; justify-content: center; }
.chatbot-toggle:hover { transform: scale(1.05); background: var(--bs-primary-dark, var(--bs-primary)); }
.chatbot-panel { position: absolute; bottom: 4.25rem; right: 0; width: 22rem; max-width: calc(100vw - 2rem); height: 32rem; max-height: calc(100vh - 7rem); background: var(--bs-body-bg); border-radius: 1rem; box-shadow: 0 8px 30px rgba(0,0,0,.18); display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--bs-border-color); }
.chatbot-header { padding: .85rem 1rem; background: var(--bs-primary); color: #fff; display: flex; align-items: center; justify-content: space-between; font-weight: 600; }
.chatbot-close { background: none; border: none; color: #fff; font-size: 1.1rem; cursor: pointer; opacity: .85; }
.chatbot-close:hover { opacity: 1; }
.chatbot-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .75rem; }
.chatbot-message { display: flex; flex-direction: column; max-width: 85%; }
.chatbot-message.user { align-self: flex-end; align-items: flex-end; }
.chatbot-message.bot { align-self: flex-start; align-items: flex-start; }
.chatbot-bubble { padding: .65rem .9rem; border-radius: 1rem; font-size: .875rem; line-height: 1.4; white-space: pre-wrap; word-break: break-word; }
.chatbot-message.user .chatbot-bubble { background: var(--bs-primary); color: #fff; border-bottom-right-radius: .25rem; }
.chatbot-message.bot .chatbot-bubble { background: var(--bs-tertiary-bg, #f1f3f5); color: var(--bs-body-color); border-bottom-left-radius: .25rem; border: 1px solid var(--bs-border-color); }
.chatbot-time { font-size: .7rem; color: var(--bs-secondary-color); margin-top: .2rem; }
.chatbot-typing { padding: 0 1rem .5rem; display: flex; gap: .25rem; }
.chatbot-typing span { width: .45rem; height: .45rem; background: var(--bs-secondary-color); border-radius: 50%; animation: chatbot-bounce 1.4s infinite ease-in-out both; }
.chatbot-typing span:nth-child(1) { animation-delay: -.32s; }
.chatbot-typing span:nth-child(2) { animation-delay: -.16s; }
@keyframes chatbot-bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
.chatbot-form { padding: .75rem 1rem; border-top: 1px solid var(--bs-border-color); display: flex; gap: .5rem; background: var(--bs-body-bg); }
.chatbot-input { flex: 1; border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .55rem .75rem; font-size: .875rem; background: var(--bs-body-bg); color: var(--bs-body-color); }
.chatbot-input:focus { outline: none; border-color: var(--bs-primary); box-shadow: 0 0 0 .15rem rgba(var(--bs-primary-rgb),.2); }
.chatbot-input::placeholder { color: var(--bs-secondary-color); opacity: .7; }
.chatbot-send { width: 2.5rem; height: 2.5rem; border: none; border-radius: .5rem; background: var(--bs-primary); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.chatbot-send:disabled { opacity: .6; cursor: not-allowed; }
@media (max-width: 480px) {
    .chatbot-panel { width: 100vw; right: -1.25rem; bottom: 4rem; height: calc(100vh - 5.5rem); max-height: none; border-radius: 1rem 1rem 0 0; }
}
</style>
@endpush

@push('scripts')
<script>
(function(){
    const toggle = document.getElementById('chatbot-toggle');
    const panel = document.getElementById('chatbot-panel');
    const close = document.getElementById('chatbot-close');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const messages = document.getElementById('chatbot-messages');
    const typing = document.getElementById('chatbot-typing');
    const sessionId = localStorage.getItem('chatbot-session') || ('sess_' + Math.random().toString(36).slice(2));
    localStorage.setItem('chatbot-session', sessionId);

    function scrollBottom(){ messages.scrollTop = messages.scrollHeight; }
    function nowTime(){ return new Date().toLocaleTimeString('es-VE', {hour:'2-digit', minute:'2-digit'}); }
    function append(text, sender){
        const div = document.createElement('div');
        div.className = 'chatbot-message ' + sender;
        div.innerHTML = '<div class="chatbot-bubble"></div><div class="chatbot-time">' + nowTime() + '</div>';
        div.querySelector('.chatbot-bubble').textContent = text;
        messages.appendChild(div);
        scrollBottom();
    }

    toggle.addEventListener('click', () => panel.classList.toggle('d-none'));
    close.addEventListener('click', () => panel.classList.add('d-none'));

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        const text = input.value.trim();
        if(!text) return;
        append(text, 'user');
        input.value = '';
        typing.classList.remove('d-none');
        scrollBottom();
        form.querySelector('.chatbot-send').disabled = true;
        try {
            const res = await fetch('{{ route("chatbot.message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: text, session_id: sessionId })
            });
            const data = await res.json();
            if(data.message) append(data.message, 'bot');
            else if(data.error) append('Error: ' + data.error, 'bot');
            else append('No recibí respuesta. Intenta de nuevo.', 'bot');
        } catch(err) {
            append('No pude conectar con el asistente. Revisa tu conexión.', 'bot');
        } finally {
            typing.classList.add('d-none');
            form.querySelector('.chatbot-send').disabled = false;
            input.focus();
        }
    });
})();
</script>
@endpush
@endauth
