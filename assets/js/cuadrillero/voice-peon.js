(function(){
  const btnStart = document.getElementById('btnDictarPeon');
  const btnStop = document.getElementById('btnPararDictadoPeon');
  const statusEl = document.getElementById('estadoDictadoPeon');
  if(!btnStart) return;

  const fields = {
    nombre: document.getElementById('peonNombre'),
    apellido: document.getElementById('peonApellido'),
    dni: document.getElementById('peonDni'),
    telefono: document.getElementById('peonTelefono')
  };

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if(!SpeechRecognition){
    statusEl && (statusEl.textContent = 'Reconocimiento de voz no soportado en este navegador');
    btnStart.classList.add('disabled');
    return;
  }

  const recognition = new SpeechRecognition();
  recognition.lang = 'es-ES';
  recognition.interimResults = true;
  recognition.continuous = true;

  let buffer = '';
  let active = false;

  function parseTranscript(text){
    // Normalizar
    let t = text.toLowerCase();
    // Reemplazar posibles palabras variantes
    t = t.replace(/tel[eé]fono/g,'telefono');
    // Extraer por tokens
    // Patrones básicos: nombre <valor>, apellido <valor>, dni <valor>, telefono <valor>
    const patterns = {
      nombre: /nombre\s+([a-záéíóúñ]+)/i,
      apellido: /apellido\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/i,
      dni: /dni\s+(\d{5,})/i,
      telefono: /telefono\s+(\d{6,})/i
    };
    Object.keys(patterns).forEach(key => {
      const m = text.match(patterns[key]);
      if(m && m[1] && fields[key]){
        fields[key].value = m[1].trim();
      }
    });
  }

  recognition.onresult = (e) => {
    let finalPiece = '';
    for(let i=e.resultIndex; i<e.results.length; i++){
      const res = e.results[i];
      if(res.isFinal){ finalPiece += res[0].transcript + ' '; }
    }
    if(finalPiece){
      buffer += finalPiece;
      parseTranscript(buffer);
      statusEl && (statusEl.textContent = 'Dictado: ' + finalPiece.trim());
    }
  };

  recognition.onerror = (e) => {
    statusEl && (statusEl.textContent = 'Error: ' + e.error);
  };

  recognition.onend = () => {
    if(active){ // si terminó inesperado, reanudar
      recognition.start();
    }
  };

  btnStart.addEventListener('click', () => {
    if(active) return;
    try {
      buffer='';
      active = true;
      recognition.start();
      statusEl && (statusEl.textContent = 'Escuchando...');
      btnStart.classList.add('d-none');
      btnStop.classList.remove('d-none');
    } catch(err){
      statusEl && (statusEl.textContent = 'No se pudo iniciar: ' + err.message);
    }
  });

  btnStop.addEventListener('click', () => {
    active = false;
    recognition.stop();
    statusEl && (statusEl.textContent = 'Dictado detenido.');
    btnStop.classList.add('d-none');
    btnStart.classList.remove('d-none');
  });
})();
