(function(){
    'use strict';
    function normalize(value){return String(value||'').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim();}
    function initWorkerSelect(select){
        if(!select||select.dataset.workerSearchReady==='1')return;
        select.dataset.workerSearchReady='1';
        select.parentElement?.classList.add('worker-select-enhanced');
        var tools=document.createElement('div');tools.className='worker-select-tools';
        var search=document.createElement('div');search.className='worker-select-search';
        var icon=document.createElement('i');icon.className='fa-solid fa-magnifying-glass';icon.setAttribute('aria-hidden','true');
        var input=document.createElement('input');input.type='search';input.className='worker-select-query';input.autocomplete='off';input.placeholder=select.dataset.workerSearchPlaceholder||'Buscar por nombre, c\u00e9dula, correo o cargo';input.setAttribute('aria-label',input.placeholder);
        var clear=document.createElement('button');clear.type='button';clear.className='worker-select-clear';clear.textContent='\u00d7';clear.title='Limpiar b\u00fasqueda';clear.setAttribute('aria-label','Limpiar b\u00fasqueda');
        var status=document.createElement('span');status.className='worker-select-status';status.setAttribute('aria-live','polite');
        search.append(icon,input,clear);tools.append(search,status);select.parentNode.insertBefore(tools,select);
        var options=Array.from(select.options).map(function(option){var dataset=Object.values(option.dataset||{}).join(' ');return{option:option,index:normalize(option.textContent+' '+dataset)};});
        var total=options.filter(function(item){return item.option.value!=='';}).length;var frame=0;
        function selectedLabel(){var option=select.selectedOptions?.[0];return option&&option.value?String(option.dataset.nombre||option.textContent||'').trim():'';}
        function renderStatus(matches){var chosen=selectedLabel();status.classList.toggle('has-selection',Boolean(chosen));status.textContent=chosen?'Seleccionado: '+chosen:(matches===total?total+' disponible'+(total===1?'':'s'):matches+' coincidencia'+(matches===1?'':'s'));}
        function filter(){var query=normalize(input.value);var terms=query.split(' ').filter(Boolean);var matches=0;options.forEach(function(item){var visible=!item.option.value||terms.every(function(term){return item.index.includes(term);});item.option.hidden=!visible;if(item.option.value&&visible)matches++;});renderStatus(matches);}
        input.addEventListener('input',function(){cancelAnimationFrame(frame);frame=requestAnimationFrame(filter);});
        clear.addEventListener('click',function(){input.value='';filter();input.focus();});
        select.addEventListener('change',function(){input.value='';filter();});
        filter();
    }
    function boot(){document.querySelectorAll('select[data-worker-search]').forEach(initWorkerSelect);}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
