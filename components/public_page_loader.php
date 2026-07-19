<?php
if (defined('PW_PUBLIC_PAGE_LOADER_RENDERED')) {
    return;
}
define('PW_PUBLIC_PAGE_LOADER_RENDERED', true);
include_once __DIR__ . '/brand_favicon.php';
?>
<script>document.documentElement.classList.add('pw-public-is-loading');</script>
<style>
    html.pw-public-is-loading,
    html.pw-public-is-loading body { overflow: hidden !important; }
    .pw-public-loader {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: grid;
        place-items: center;
        padding: 24px;
        background:
            radial-gradient(circle at 18% 18%, rgba(43,90,158,.12), transparent 34%),
            radial-gradient(circle at 82% 78%, rgba(255,122,0,.11), transparent 32%),
            #f7faff;
        opacity: 1;
        visibility: visible;
        transition: opacity .42s ease, visibility .42s ease;
    }
    .pw-public-loader::before,
    .pw-public-loader::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .pw-public-loader::before {
        width: min(42vw, 520px);
        height: min(42vw, 520px);
        top: -25%;
        right: -10%;
        border: 1px solid rgba(37,99,235,.09);
        box-shadow: 0 0 0 46px rgba(37,99,235,.025), 0 0 0 92px rgba(37,99,235,.018);
    }
    .pw-public-loader::after {
        width: 260px;
        height: 260px;
        left: -105px;
        bottom: -105px;
        border: 38px solid rgba(255,122,0,.035);
    }
    html.pw-public-page-ready .pw-public-loader {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    html.pw-public-page-leaving .pw-public-loader {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition-duration: .18s;
    }
    .pw-loader-content {
        position: relative;
        z-index: 1;
        width: min(350px, 100%);
        text-align: center;
    }
    .pw-loader-brand {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 22px;
    }
    .pw-loader-wordmark { display: flex; align-items: center; justify-content: center; }
    .pw-loader-wordmark strong { color: #173b8f; font-family: Inter,Arial,sans-serif; font-size: 1.42rem; font-weight: 900; letter-spacing: -.045em; line-height: 1; }
    .pw-loader-wordmark strong span { color: #ff7500; }
    .pw-loader-track {
        width: 100%;
        height: 5px;
        overflow: hidden;
        border-radius: 999px;
        background: #dfe8f3;
    }
    .pw-loader-track span {
        display: block;
        width: 42%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg,#ff7500,#ffae55,#2563eb);
        animation: pwLoaderProgress 1.15s cubic-bezier(.4,0,.2,1) infinite;
    }
    .pw-loader-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        margin: 12px 0 0;
        color: #6c7d95;
        font-family: Inter,Arial,sans-serif;
        font-size: .66rem;
        font-weight: 650;
    }
    .pw-loader-status i { width: 5px; height: 5px; border-radius: 50%; background: #ff7500; animation: pwLoaderDot 1.15s ease-in-out infinite; }
    @keyframes pwLoaderProgress { 0% { transform: translateX(-115%); } 55% { transform: translateX(105%); } 100% { transform: translateX(245%); } }
    @keyframes pwLoaderDot { 0%,100% { opacity: .35; transform: scale(.8); } 50% { opacity: 1; transform: scale(1.25); } }
    @media (max-width: 520px) {
        .pw-loader-content { width: min(290px, 88vw); }
        .pw-loader-wordmark strong { font-size: 1.25rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pw-loader-track span,.pw-loader-status i { animation: none; }
        .pw-loader-track span { width: 100%; transform: none; }
        .pw-public-loader { transition-duration: .12s; }
    }
</style>
<div class="pw-public-loader" id="pwPublicLoader" role="status" aria-live="polite" aria-label="Cargando PreventWork">
    <div class="pw-loader-content">
        <div class="pw-loader-brand">
            <span class="pw-loader-wordmark"><strong>PREVENT<span>WORK</span></strong></span>
        </div>
        <div class="pw-loader-track" aria-hidden="true"><span></span></div>
        <p class="pw-loader-status"><i></i> Preparando tu experiencia</p>
    </div>
</div>
<noscript><style>.pw-public-loader{display:none!important}</style></noscript>
<script>
(function(){
    if(window.__pwPublicLoaderReady)return;
    window.__pwPublicLoaderReady=true;
    var html=document.documentElement;
    var started=Date.now();
    var minimum=520;
    var finished=false;

    function hideLoader(immediate){
        if(finished&&!immediate)return;
        finished=true;
        var delay=immediate?0:Math.max(0,minimum-(Date.now()-started));
        window.setTimeout(function(){
            html.classList.remove('pw-public-is-loading','pw-public-page-leaving');
            html.classList.add('pw-public-page-ready');
        },delay);
    }
    function showLoader(){
        html.classList.remove('pw-public-page-ready');
        html.classList.add('pw-public-is-loading','pw-public-page-leaving');
    }

    if(document.readyState==='complete'){hideLoader(false)}else{window.addEventListener('load',function(){hideLoader(false)},{once:true})}
    window.setTimeout(function(){hideLoader(false)},5000);
    window.addEventListener('pageshow',function(event){if(event.persisted)hideLoader(true)});

    document.addEventListener('click',function(event){
        if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
        var link=event.target.closest('a[href]');
        if(!link||link.hasAttribute('download')||(link.target&&link.target.toLowerCase()!=='_self'))return;
        var raw=(link.getAttribute('href')||'').trim();
        if(!raw||raw==='#'||raw.charAt(0)==='#'||/^(mailto:|tel:|javascript:)/i.test(raw))return;
        var destination;
        try{destination=new URL(link.href,window.location.href)}catch(error){return}
        if(destination.origin!==window.location.origin)return;
        if(destination.pathname===window.location.pathname&&destination.search===window.location.search&&destination.hash)return;
        showLoader();
    });
    document.addEventListener('submit',function(event){if(!event.defaultPrevented)showLoader()});
}());
</script>
