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
    // Normalizar y limpiar caracteres no deseados
    let lower = text.toLowerCase()
      .replace(/tel[eé]fono/g,'telefono')
      .replace(/[,:.;]/g,' ')
      .replace(/\s+/g,' ');

    // Palabras clave y palabras ruido que delimitan
    const delimiters = '(nombre|apellido|dni|telefono|presente|ausente)';

    function extractWords(keyword){
      const re = new RegExp(keyword + '\\s+(.+?)(?=\\s+' + delimiters + '|$)','i');
      const m = lower.match(re);
      if(!m) return null;
      const onlyLetters = (m[1].match(/[a-záéíóúñ]+/gi) || []).join(' ').trim();
      return onlyLetters || null;
    }

    // Nombre y apellido
    const nombreVal = extractWords('nombre');
    if(nombreVal && fields.nombre){ fields.nombre.value = nombreVal; }
    const apellidoVal = extractWords('apellido');
    if(apellidoVal && fields.apellido){ fields.apellido.value = apellidoVal; }

    // DNI: permitir espacios entre dígitos (ej: "1 2 3 0 5 6") y unirlos
    const dniRe = /dni\s+([0-9 ]{5,})(?=\s+(nombre|apellido|dni|telefono|presente|ausente)|$)/i;
    const dniMatch = lower.match(dniRe);
    if(dniMatch && fields.dni){
      const digits = dniMatch[1].replace(/\s+/g,'');
      fields.dni.value = digits;
    }

    // Teléfono similar
    const telRe = /telefono\s+([0-9 ]{6,})(?=\s+(nombre|apellido|dni|telefono|presente|ausente)|$)/i;
    const telMatch = lower.match(telRe);
    if(telMatch && fields.telefono){
      const digitsTel = telMatch[1].replace(/\s+/g,'');
      fields.telefono.value = digitsTel;
    }

    // Segunda pasada: si DNI o Teléfono quedaron vacíos y existen palabras numéricas
    if(fields.dni && fields.dni.value.trim()===''){
      const dniWordsRe = /dni\s+([a-záéíóúñ\s]+?)(?=\s+(nombre|apellido|dni|telefono|presente|ausente)|$)/i;
      const dniWords = lower.match(dniWordsRe);
      if(dniWords){
        const converted = wordsToDigits(dniWords[1]);
        if(converted) fields.dni.value = converted;
      }
    }
    if(fields.telefono && fields.telefono.value.trim()===''){
      const telWordsRe = /telefono\s+([a-záéíóúñ\s]+?)(?=\s+(nombre|apellido|dni|telefono|presente|ausente)|$)/i;
      const telWords = lower.match(telWordsRe);
      if(telWords){
        const convertedTel = wordsToDigits(telWords[1]);
        if(convertedTel) fields.telefono.value = convertedTel;
      }
    }
  }

  // Convierte secuencias de palabras numéricas españolas a dígitos.
  function wordsToDigits(segment){
    if(!segment) return '';
    segment = segment.trim();
    // Unificar variantes
    segment = segment.replace(/veinti(\w+)/g,'veinti $1'); // separa veintidos -> veinti dos
    segment = segment.replace(/dieci(\w+)/g,'dieci $1'); // dieciseis -> dieci seis
    const tokens = segment.split(/\s+/).filter(t=>t!=='y');
    if(!tokens.length) return '';
    const units = {
      'cero':0,'uno':1,'un':1,'dos':2,'tres':3,'cuatro':4,'cinco':5,'seis':6,'siete':7,'ocho':8,'nueve':9
    };
    const teens = {
      'diez':10,'once':11,'doce':12,'trece':13,'catorce':14,'quince':15,'dieci seis':16,'dieciseis':16,'dieci siete':17,'diecisiete':17,'dieci ocho':18,'dieciocho':18,'dieci nueve':19,'diecinueve':19
    };
    const tens = { 'veinte':20,'veinti':20,'treinta':30,'cuarenta':40,'cincuenta':50,'sesenta':60,'setenta':70,'ochenta':80,'noventa':90 };
    let digits='';
    for(let i=0;i<tokens.length;i++){
      let tk = tokens[i];
      // Unir secuencias 'dieci seis'
      if(i+1<tokens.length && (tk==='dieci'||tk==='veinti')){ tk = tk+' '+tokens[i+1]; tokens.splice(i+1,1); }
      // Teens exactos
      if(teens.hasOwnProperty(tk)){ digits += teens[tk].toString(); continue; }
      // Tens + unit (treinta cinco -> 35)
      if(tens.hasOwnProperty(tk)){
        if(i+1<tokens.length && units.hasOwnProperty(tokens[i+1])){
          digits += (tens[tk] + units[tokens[i+1]]).toString();
          i++; // consumir unidad
        } else {
          digits += tens[tk].toString();
        }
        continue;
      }
      // Units
      if(units.hasOwnProperty(tk)){
        digits += units[tk].toString();
        continue;
      }
      // Si token es número ya
      if(/^[0-9]+$/.test(tk)){ digits += tk; continue; }
    }
    return digits;
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
