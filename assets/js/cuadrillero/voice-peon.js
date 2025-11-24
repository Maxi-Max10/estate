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
    // Normalizar a minúsculas para búsqueda y unificar teléfono
    let lower = text.toLowerCase().replace(/tel[eé]fono/g,'telefono');
    // Lista de palabras clave para delimitar campos
    const delimiters = '(nombre|apellido|dni|telefono)';

    // Helper para extraer secciones entre palabra clave y siguiente palabra clave
    function extract(keyword, regex){
      const base = new RegExp(keyword + '\\s+(.+?)(?=\\s+' + delimiters + '|$)','i');
      const match = lower.match(base);
      if(match){
        let raw = match[1].trim();
        if(regex){ raw = (raw.match(regex) || []).join(' ').trim(); }
        return raw;
      }
      return null;
    }

    const nombreVal = extract('nombre', /[a-záéíóúñ]+/gi);
    if(nombreVal && fields.nombre){ fields.nombre.value = nombreVal; }

    const apellidoVal = extract('apellido', /[a-záéíóúñ]+/gi);
    if(apellidoVal && fields.apellido){ fields.apellido.value = apellidoVal; }

    const dniMatch = lower.match(/dni\s+(\d{5,})/i);
    if(dniMatch && fields.dni){ fields.dni.value = dniMatch[1]; }

    const telMatch = lower.match(/telefono\s+(\d{6,})/i);
    if(telMatch && fields.telefono){ fields.telefono.value = telMatch[1]; }
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
