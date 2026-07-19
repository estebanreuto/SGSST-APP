<?php
session_start();
require_once 'config/db.php';

// Traer los planes y características de la base de datos para mostrarlos al público
$stmt_planes = $conn->query("SELECT * FROM planes ORDER BY id ASC");
$planes_db = $stmt_planes->fetchAll(PDO::FETCH_ASSOC);

$planes_info = [];
foreach ($planes_db as $plan) {
    $stmt_feat = $conn->prepare("SELECT * FROM plan_caracteristicas WHERE plan_id = ? ORDER BY id ASC");
    $stmt_feat->execute([$plan['id']]);
    $features_db = $stmt_feat->fetchAll(PDO::FETCH_ASSOC);
    
    $features = [];
    foreach ($features_db as $f) {
        $features[] = [
            'texto' => $f['texto'],
            'incluido' => (bool)$f['incluido']
        ];
    }
    $plan['features'] = $features;
    $planes_info[] = $plan;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>PREVENTWORK | Gestión Integral SG-SST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --bg-top: #ffffff;
      --bg-mid: #f8fafc;
      --bg-bottom: #ffffff;
      --accent: #ff8a1f;
      --accent-hover: #ff7a00;
      
      /* Variables añadidas para que los planes funcionen perfecto */
      --primary: #ff8a1f; 
      --primary2: #ff7a00;
      
      --blue-main: #2b5a9e;
      --blue-dark: #0f172a;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --card-bg: #ffffff;
      --card-border: #e2e8f0;
      --shadow-soft: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
      --radius-lg: 24px;
      --radius-md: 12px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    
    body { font-family: 'Inter', sans-serif; color: var(--text-main); background: #ffffff; overflow-x: hidden; width: 100%; position: relative; }
    .page-wrapper { width: 100%; overflow-x: hidden; position: relative; }

    .bg-gradient { position: absolute; top: 0; left: 0; right: 0; height: 100vh; background: radial-gradient(circle at top right, #f1f5f9 0%, #ffffff 100%); z-index: -2; }
    .blob { position: absolute; border-radius: 50%; filter: blur(90px); z-index: -1; opacity: 0.15; animation: float 10s infinite ease-in-out alternate; }
    .blob-1 { top: -5%; left: -10%; width: 500px; height: 500px; background: var(--blue-main); }
    .blob-2 { top: 20%; right: -5%; width: 600px; height: 600px; background: var(--accent); animation-delay: -5s; }

    @keyframes float { 0% { transform: translateY(0px) scale(1); } 100% { transform: translateY(30px) scale(1.05); } }

    .fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

    .btn-primary, .btn-secondary { padding: 14px 28px; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; border: none; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: #fff; box-shadow: 0 10px 25px rgba(255, 138, 31, 0.25); }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(255, 138, 31, 0.35); color: #fff; }
    .btn-secondary { background: #ffffff; border: 1px solid #cbd5e1; color: var(--text-main); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02); }
    .btn-secondary:hover { border-color: var(--blue-main); color: var(--blue-main); background: #f8fafc; transform: translateY(-3px); }

    .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 140px 6vw 60px; position: relative;}
    .hero-grid { max-width: 1280px; width: 100%; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 60px; align-items: center; }

    .hero-tag { display: inline-block; background: #f1f5f9; color: var(--blue-main); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .hero-title { font-size: clamp(2.35rem, 4vw, 3.75rem); line-height: 1.1; font-weight: 800; margin-bottom: 24px; letter-spacing: -0.03em; color: var(--blue-dark); }
    .hero-title span { background: linear-gradient(135deg, var(--accent), #ff5e00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero-text { font-size: 1rem; color: var(--text-muted); max-width: 500px; line-height: 1.6; margin-bottom: 36px; }
    .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; position: relative; z-index: 10; }
    .hero-actions .btn-primary,
    .hero-actions .btn-secondary { min-height: 42px; padding: 10px 18px; border-radius: 10px; font-size: .86rem; gap: 8px; }

    .visual-container { position: relative; width: 100%; height: 550px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }

    .phone-mockup { width: 280px; height: 560px; background: #ffffff; border: 12px solid #0f172a; border-radius: 40px; position: relative; box-shadow: 0 30px 60px rgba(0,0,0,0.15); z-index: 5; overflow: hidden; animation: floatPhone 6s ease-in-out infinite; }
    .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 120px; height: 25px; background: #0f172a; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; z-index: 10; }
    .phone-screen { width: 100%; height: 100%; background: #f8fafc; padding: 40px 16px 20px; display: flex; flex-direction: column; gap: 16px; position: relative; }

    .mock-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .mock-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; }
    .mock-lines { display: flex; flex-direction: column; gap: 6px; flex: 1;}
    .mock-line-1 { height: 10px; width: 60%; background: #cbd5e1; border-radius: 4px; }
    .mock-line-2 { height: 8px; width: 40%; background: #e2e8f0; border-radius: 4px; }

    .mock-chart { background: white; border-radius: 12px; padding: 16px; height: 120px; display: flex; align-items: flex-end; gap: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); justify-content: center; }
    .mock-bar { width: 25%; background: var(--blue-main); border-radius: 4px 4px 0 0; }
    .mock-bar.b1 { animation: barGrow1 3s infinite alternate ease-in-out; }
    .mock-bar.b2 { background: var(--accent); animation: barGrow2 3.5s infinite alternate ease-in-out; }
    .mock-bar.b3 { background: #94a3b8; animation: barGrow3 2.5s infinite alternate ease-in-out; }

    .mock-list { display: flex; flex-direction: column; gap: 10px; }
    .mock-item { background: white; height: 45px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); animation: slideUp 4s infinite linear; opacity: 0; }
    .mock-item.m-1 { animation-delay: 0s; }
    .mock-item.m-2 { animation-delay: 1s; }
    .mock-item.m-3 { animation-delay: 2s; }

    @keyframes floatPhone { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
    @keyframes barGrow1 { 0% { height: 30%; } 100% { height: 80%; } }
    @keyframes barGrow2 { 0% { height: 50%; } 100% { height: 100%; } }
    @keyframes barGrow3 { 0% { height: 20%; } 100% { height: 60%; } }
    @keyframes slideUp { 0% { transform: translateY(20px); opacity: 0; } 10% { transform: translateY(0); opacity: 1; } 80% { transform: translateY(0); opacity: 1; } 100% { transform: translateY(-20px); opacity: 0; } }

    .floating-badge { position: absolute; background: white; padding: 12px 18px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 0.85rem; border: 1px solid #f1f5f9; white-space: nowrap; animation: floatBadge 6s infinite ease-in-out; z-index: 6; color: var(--blue-dark); }
    .floating-badge i { color: var(--accent); font-size: 1rem; }

    .badge-1 { top: 5%; left: 0; animation-delay: 0s; } 
    .badge-2 { bottom: 15%; right: -5%; animation-delay: 1s; } 
    .badge-3 { top: 40%; left: -10%; animation-delay: 2s; } 
    .badge-4 { top: 20%; right: 0; animation-delay: 3s; } 
    .badge-5 { bottom: 25%; left: -5%; animation-delay: 4s; } 

    @keyframes floatBadge { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

    /* =========================================
       SECCIÓN DE CARACTERÍSTICAS
       ========================================= */
    .features { padding: 80px 6vw; background: #ffffff; position: relative; z-index: 2; }
    
    .section-header { text-align: center; max-width: 700px; margin: 0 auto 40px; }
    .section-header h2 { font-size: 1.88rem; color: var(--blue-dark); font-weight: 800; margin-bottom: 12px; letter-spacing: -0.02em; }
    .section-header p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; }
    
    .features-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 24px; max-width: 1200px; margin: 0 auto; }
    .feature-card { width: calc(25% - 18px); padding: 24px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; transition: all 0.3s ease; text-align: left; position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 10px; }
    .feature-card:hover { transform: translateY(-4px); box-shadow: 0 12px 25px rgba(0, 0, 0, 0.05); border-color: #cbd5e1; }
    
    .feature-icon { width: 44px; height: 44px; background: rgba(43, 90, 158, 0.08); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--blue-main); z-index: 2; position: relative; }
    .feature-card h3 { font-size: 1.05rem; font-weight: 800; color: var(--blue-dark); margin: 0; z-index: 2; position: relative; }
    .feature-card p { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0; z-index: 2; position: relative; }

    .watermark-icon { position: absolute; right: -10px; bottom: -15px; font-size: 100px; color: var(--blue-main); opacity: 0.03; transform: rotate(-15deg); transition: all 0.4s ease; z-index: 1; pointer-events: none; }
    .feature-card:hover .watermark-icon { transform: rotate(0deg) scale(1.05); opacity: 0.06; color: var(--accent); }

    /* =========================================
       SECCIÓN DE PLANES (GRILLA CENTRADA)
       ========================================= */
    .pricing-section { padding: 80px 6vw; background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; position: relative; z-index: 2;}
    
    .pricing-grid { 
        display: flex; justify-content: center; flex-wrap: wrap; gap: 24px; 
        max-width: 1100px; margin: 0 auto; padding-top: 20px;
    }

    .plan-card { 
        flex: 1 1 300px; max-width: 350px;
        background: #ffffff; border: 1px solid var(--border); border-radius: 20px; 
        padding: 40px 24px 24px; display: flex; flex-direction: column; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; 
        position: relative; z-index: 1;
    }
    .plan-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.06); border-color: #cbd5e1;}
    
    /* Contenedor interno para cortar SOLO la marca de agua y no dañar el botón ni la tarjeta */
    .plan-card-inner {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 20px; overflow: hidden; pointer-events: none; z-index: 0;
    }

    /* Estilos del plan popular */
    .plan-card.popular { 
        border: 2px solid var(--primary); 
        box-shadow: 0 12px 30px rgba(255, 138, 31, 0.08); z-index: 2; 
        transform: scale(1.03); 
    }
    .plan-card.popular:hover { transform: scale(1.03) translateY(-5px); box-shadow: 0 15px 35px rgba(255, 138, 31, 0.15); }
    
    .popular-badge { 
        position: absolute; top: -14px; left: 50%; transform: translateX(-50%); 
        background: linear-gradient(135deg, var(--primary), var(--primary2)); color: white; 
        padding: 6px 20px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 0.05em; 
        box-shadow: 0 4px 12px rgba(255, 138, 31, 0.3); z-index: 10; white-space: nowrap;
    }
    
    /* Marca de agua Planes */
    .plan-watermark { 
        position: absolute; right: -20px; bottom: -20px; font-size: 160px; line-height: 1; 
        opacity: 0.03; transform: rotate(-15deg); transition: all 0.4s ease; 
    }
    .plan-card:hover .plan-watermark { transform: rotate(0deg) scale(1.1); opacity: 0.06; }
    
    .watermark-blue { color: #3b82f6; }
    .watermark-orange { color: var(--primary); }
    .watermark-purple { color: #8b5cf6; }

    .plan-content-wrapper { position: relative; z-index: 2; display: flex; flex-direction: column; height: 100%;}
    
    .plan-header { text-align: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
    .plan-title { font-size: 1.2rem; color: var(--blue-dark); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0; }
    .popular .plan-title { color: var(--primary); }
    
    .price-container { display: flex; flex-direction: column; align-items: center; gap: 2px; }
    .plan-price-old { font-size: 0.9rem; color: #94a3b8; text-decoration: line-through; font-weight: 600; margin: 0; height: 18px; }
    .plan-price { font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0; line-height: 1; display: flex; justify-content: center; align-items: baseline; gap: 4px;}
    .plan-price span { font-size: 0.9rem; color: var(--muted); font-weight: 600; }
    
    .plan-features { list-style: none; padding: 0; margin: 0 0 32px 0; flex: 1; display: flex; flex-direction: column; gap: 14px; }
    .plan-features li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; color: #334155; font-weight: 500; line-height: 1.4;}
    .plan-features li i.fa-check { color: #10b981; flex-shrink: 0; margin-top: 2px; font-size: 0.9rem;}
    .plan-features li.disabled { color: #94a3b8; text-decoration: line-through; }
    .plan-features li.disabled i.fa-xmark { color: #cbd5e1; flex-shrink: 0; margin-top: 2px; font-size: 0.9rem;}
    
    .btn-plan-select { 
        width: 100%; padding: 14px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; 
        text-align: center; text-decoration: none; transition: all 0.2s; display: block; 
        margin-top: auto; position: relative; z-index: 5;
    }
    .btn-plan-outline { background: #f8fafc; color: var(--blue-dark); border: 1px solid #cbd5e1; }
    .btn-plan-outline:hover { background: #f1f5f9; border-color: var(--blue-main); color: var(--blue-main);}
    
    /* ¡AQUÍ ESTABA EL ERROR DEL BOTÓN BLANCO! */
    .btn-plan-solid { 
        background: linear-gradient(135deg, var(--primary), var(--primary2)); 
        color: white; border: none; 
        box-shadow: 0 4px 12px rgba(255, 138, 31, 0.25); 
    }
    .btn-plan-solid:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 138, 31, 0.35); color: white;}

    /* =========================================
       CONTENIDO POST-PLANES
       ========================================= */
    .landing-band { padding: 88px 6vw; position: relative; z-index: 2; }
    .landing-inner { width: 100%; max-width: 1180px; margin: 0 auto; }
    .landing-kicker { color: var(--primary2); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; }
    .landing-heading { max-width: 700px; margin-bottom: 42px; }
    .landing-heading h2 { margin: 0 0 12px; color: var(--blue-dark); font-size: 1.88rem; line-height: 1.2; font-weight: 800; }
    .landing-heading p { margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.7; }

    .process-band { background: #ffffff; }
    .process-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
    .process-step { padding: 28px 24px; position: relative; min-height: 190px; }
    .process-step + .process-step { border-left: 1px solid #e2e8f0; }
    .process-number { color: var(--primary); font-size: 0.75rem; font-weight: 800; margin-bottom: 24px; }
    .process-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #eef4fb; color: var(--blue-main); margin-bottom: 18px; border: 1px solid #dbe7f5; box-shadow: 0 8px 18px rgba(43, 90, 158, 0.08); }
    .process-step h3 { margin: 0 0 8px; font-size: 1rem; color: var(--blue-dark); }
    .process-step p { margin: 0; color: var(--text-muted); font-size: 0.82rem; line-height: 1.55; }

    .roles-band { background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
    .roles-layout { display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 72px; align-items: center; }
    .roles-copy h2 { margin: 0 0 16px; color: var(--blue-dark); font-size: 1.88rem; line-height: 1.2; }
    .roles-copy p { margin: 0 0 26px; color: var(--text-muted); line-height: 1.7; }
    .roles-copy a { color: var(--blue-main); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
    .role-list { display: grid; gap: 1px; background: #dbe3ec; border: 1px solid #dbe3ec; }
    .role-row { display: grid; grid-template-columns: 48px 1fr; gap: 18px; align-items: start; padding: 24px; background: #ffffff; }
    .role-row-icon { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #fff4e8; color: var(--primary2); border: 1px solid #fed7aa; box-shadow: 0 8px 18px rgba(255, 138, 31, 0.09); }
    .role-row h3 { margin: 0 0 6px; color: var(--text-main); font-size: 1rem; }
    .role-row p { margin: 0; color: var(--text-muted); font-size: 0.82rem; line-height: 1.55; }

    .evidence-band { background: #ffffff; }
    .evidence-layout { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 64px; align-items: center; }
    .evidence-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-top: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; }
    .evidence-item { padding: 24px; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
    .evidence-item > i { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0f766e; background: #ecfdf5; border: 1px solid #a7f3d0; margin-bottom: 14px; font-size: 0.82rem; }
    .evidence-item h3 { margin: 0 0 6px; color: var(--blue-dark); font-size: 0.95rem; }
    .evidence-item p { margin: 0; color: var(--text-muted); font-size: 0.8rem; line-height: 1.5; }
    .compliance-panel { background: #0f172a; color: #ffffff; padding: 42px 38px; border-radius: 8px; position: relative; overflow: hidden; isolation: isolate; }
    .compliance-watermark { position: absolute; right: -24px; bottom: -32px; color: #ffffff; opacity: 0.055; font-size: 190px; transform: rotate(-10deg); z-index: -1; pointer-events: none; }
    .compliance-panel h2 { margin: 0 0 14px; font-size: 1.65rem; line-height: 1.25; max-width: 390px; }
    .compliance-panel p { margin: 0 0 24px; color: #cbd5e1; line-height: 1.65; font-size: 0.9rem; max-width: 410px; }
    .compliance-panel a { color: #ffffff; font-weight: 700; text-decoration: none; display: inline-flex; gap: 8px; align-items: center; }

    .final-cta { min-height: 390px; padding: 76px 6vw; color: #ffffff; position: relative; display: flex; align-items: center; background-image: linear-gradient(90deg, rgba(9, 29, 56, 0.98) 0%, rgba(15, 42, 75, 0.9) 42%, rgba(15, 42, 75, 0.28) 72%, rgba(15, 42, 75, 0.1) 100%), url('assets/cta-equipo-sst.png'); background-size: cover; background-position: center; }
    .final-cta-inner { width: 100%; max-width: 1180px; margin: 0 auto; position: relative; }
    .final-cta-copy { max-width: 570px; }
    .final-cta-kicker { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 18px; color: #fed7aa; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .final-cta-kicker i { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff; background: var(--primary); }
    .final-cta h2 { margin: 0 0 12px; font-size: 2rem; line-height: 1.2; max-width: 560px; }
    .final-cta p { margin: 0 0 28px; color: #dbeafe; line-height: 1.65; max-width: 500px; }
    .final-cta .btn-primary { background: var(--primary); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2); width: auto; }

    /* =========================================
       MEDIA QUERIES RESPONSIVES
       ========================================= */
    @media (max-width: 992px) {
      .hero-grid { grid-template-columns: 1fr; gap: 60px; text-align: center; }
      .hero-text { margin: 0 auto 30px; max-width: 600px;}
      .hero-actions { justify-content: center; }
      .visual-container { transform: scale(0.9); height: 500px; margin-top: 20px; }
      .feature-card { width: calc(50% - 12px); } 
      .plan-card.popular { transform: none; }
      .plan-card.popular:hover { transform: translateY(-5px); }
      .process-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .process-step:nth-child(3) { border-left: 0; border-top: 1px solid #e2e8f0; }
      .process-step:nth-child(4) { border-top: 1px solid #e2e8f0; }
      .roles-layout, .evidence-layout { grid-template-columns: 1fr; gap: 40px; }
    }

    @media (max-width: 768px) {
      .hero { padding-top: 150px; padding-bottom: 100px; height: auto;}
      .hero-title { font-size: 2.18rem; }
      .hero-actions { flex-direction: column; width: 100%; gap: 12px; margin-bottom: 30px; }
      .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
      .visual-container { transform: scale(0.7); transform-origin: top center; height: 420px; margin-top: 50px; }
      .section-header h2 { font-size: 1.7rem; }
      .features, .pricing-section { padding: 60px 6vw; }
      .pricing-grid { flex-direction: column; align-items: center; gap: 30px;}
      .plan-card { width: 100%; max-width: 400px;}
      .landing-band { padding: 64px 6vw; }
      .landing-heading h2, .roles-copy h2, .final-cta h2 { font-size: 1.55rem; }
      .process-grid { grid-template-columns: 1fr; }
      .process-step + .process-step, .process-step:nth-child(3), .process-step:nth-child(4) { border-left: 0; border-top: 1px solid #e2e8f0; }
      .process-step { min-height: auto; }
      .evidence-list { grid-template-columns: 1fr; }
      .final-cta { min-height: 430px; background-position: 62% center; }
      .final-cta .btn-primary { width: 100%; }
    }

    @media (max-width: 480px) {
      .hero { padding-top: 140px; padding-bottom: 100px; height: auto;} 
      .hero-title { font-size: 1.9rem; margin-bottom: 16px; }
      .hero-text { font-size: 0.95rem; }
      .visual-container { transform: scale(0.6); transform-origin: top center; height: 400px; margin-top: 65px;}
      
      .badge-1 { left: 15%; top: 5%; } 
      .badge-4 { right: 5%; top: 20%; } 
      .badge-3 { left: 5%; top: 40%; } 
      .badge-5 { left: 10%; bottom: 15%; } 
      .badge-2 { right: 0%; bottom: 0%; } 
      
      .feature-card { width: 100%; } 
    }

    .demo-sales-section { padding: 82px 6vw; background: linear-gradient(145deg,#0f172a,#102a67); color:#fff; position:relative; overflow:hidden; }
    .demo-sales-watermark { position:absolute; right:-40px; bottom:-70px; color:#fff; opacity:.035; font-size:360px; transform:rotate(-10deg); pointer-events:none; }
    .demo-sales-inner { position:relative; z-index:1; width:min(1180px,100%); margin:auto; display:grid; grid-template-columns:.88fr 1.12fr; gap:58px; align-items:center; }
    .demo-sales-kicker { display:inline-flex; align-items:center; gap:7px; padding:7px 10px; border:1px solid rgba(255,255,255,.18); border-radius:99px; background:rgba(255,255,255,.08); color:#fdba74; font-size:.66rem; font-weight:800; text-transform:uppercase; }
    .demo-sales-copy h2 { margin:17px 0 12px; max-width:520px; color:#fff; font-size:clamp(1.65rem,2.8vw,2.45rem); line-height:1.12; letter-spacing:-.03em; }
    .demo-sales-copy>p { margin:0; max-width:560px; color:#cbd5e1; font-size:.88rem; line-height:1.65; }
    .demo-sales-benefits { display:grid; gap:9px; margin:24px 0; }
    .demo-sales-benefits span { display:flex; align-items:center; gap:8px; color:#e2e8f0; font-size:.72rem; font-weight:600; }
    .demo-sales-benefits i { color:#34d399; }
    .demo-sales-actions { display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
    .demo-sales-actions a { min-height:43px; padding:0 14px; border-radius:9px; font-size:.72rem; }
    .demo-sales-note { color:#94a3b8; font-size:.58rem; }
    .demo-product-window { overflow:hidden; border:1px solid rgba(255,255,255,.16); border-radius:16px; background:#f8fafc; box-shadow:0 30px 70px rgba(0,0,0,.28); transform:rotate(1deg); transition:transform .35s ease; }
    .demo-product-window:hover { transform:rotate(0) translateY(-4px); }
    .demo-window-top { height:38px; display:flex; align-items:center; gap:6px; padding:0 12px; border-bottom:1px solid #e2e8f0; background:#fff; }
    .demo-window-top i { width:7px; height:7px; border-radius:50%; background:#cbd5e1; }
    .demo-window-top i:first-child { background:#fb7185; }.demo-window-top i:nth-child(2){background:#fbbf24}.demo-window-top i:nth-child(3){background:#34d399}
    .demo-window-body { display:grid; grid-template-columns:126px 1fr; min-height:350px; }
    .demo-window-sidebar { padding:13px 9px; background:#fff; border-right:1px solid #e2e8f0; }
    .demo-window-logo { height:18px; width:80px; margin:3px 5px 16px; border-radius:4px; background:linear-gradient(90deg,#102a67 65%,#ff7a00 65%); opacity:.85; }
    .demo-window-link { height:27px; display:flex; align-items:center; gap:7px; margin-bottom:4px; padding:0 7px; border-radius:6px; color:#64748b; font-size:.45rem; font-weight:700; }
    .demo-window-link.active { background:#fff3e8; color:#c2410c; }.demo-window-link i{width:12px;text-align:center}
    .demo-window-content { padding:13px; color:#1e293b; }
    .demo-window-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .demo-window-header strong { color:#102a67; font-size:.65rem; }.demo-window-header span{padding:4px 7px;border-radius:99px;background:#fff3e8;color:#c2410c;font-size:.4rem;font-weight:800}
    .demo-window-metrics { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
    .demo-window-metric { position:relative; overflow:hidden; min-height:62px; padding:8px; border:1px solid #dbe3ec; border-radius:7px; background:#fff; }
    .demo-window-metric span,.demo-window-metric strong{display:block;position:relative;z-index:1}.demo-window-metric span{color:#64748b;font-size:.36rem;font-weight:800;text-transform:uppercase}.demo-window-metric strong{margin-top:7px;color:#102a67;font-size:.82rem}.demo-window-metric i{position:absolute;right:2px;bottom:-7px;color:#2563eb;opacity:.08;font-size:2rem}
    .demo-window-panel { margin-top:7px; padding:8px; border:1px solid #dbe3ec; border-radius:7px; background:#fff; }
    .demo-window-panel>strong { display:block; margin-bottom:6px; color:#102a67; font-size:.5rem; }
    .demo-window-row { display:grid; grid-template-columns:20px 1fr auto; gap:6px; align-items:center; min-height:38px; border-top:1px solid #edf2f7; }
    .demo-window-row>i { color:#ff7a00; font-size:.48rem; }.demo-window-row div span,.demo-window-row div strong{display:block}.demo-window-row div strong{color:#334155;font-size:.43rem}.demo-window-row div span{margin-top:2px;color:#94a3b8;font-size:.35rem}.demo-window-row>b{color:#059669;font-size:.38rem}
    @media(max-width:900px){.demo-sales-inner{grid-template-columns:1fr}.demo-product-window{transform:none}.demo-sales-section{padding:64px 5vw}}
    @media(max-width:560px){.demo-sales-section{padding:52px 14px}.demo-sales-inner{gap:34px}.demo-window-body{grid-template-columns:82px 1fr;min-height:310px}.demo-window-sidebar{padding:9px 5px}.demo-window-link{padding:0 4px;font-size:.35rem}.demo-window-logo{width:60px}.demo-window-content{padding:9px}.demo-window-metrics{grid-template-columns:repeat(2,1fr)}.demo-window-metric:nth-child(n+3){display:none}.demo-sales-actions .btn-primary{width:100%}}

    .ai-preview-section { padding: 72px 6vw; background: #f8fafc; position: relative; overflow: hidden; }
    .ai-preview-shell { position: relative; isolation: isolate; width: min(1180px,100%); margin: auto; display: grid; grid-template-columns: minmax(0,.92fr) minmax(360px,.78fr); gap: 52px; align-items: center; overflow: hidden; padding: 44px; border: 1px solid #dbe3ec; border-radius: 18px; background: linear-gradient(135deg,#fff 0%,#f5f9ff 58%,#fff7ed 100%); box-shadow: 0 18px 45px rgba(15,23,42,.07); }
    .ai-preview-watermark { position: absolute; right: -35px; bottom: -68px; z-index: -1; color: #2563eb; opacity: .035; font-size: 250px; transform: rotate(-10deg); }
    .ai-preview-kicker { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border: 1px solid #fed7aa; border-radius: 99px; background: #fff7ed; color: #c2410c; font-size: .65rem; font-weight: 850; text-transform: uppercase; }
    .ai-preview-copy h2 { max-width: 600px; margin: 16px 0 11px; color: var(--blue-dark); font-size: clamp(1.65rem,2.6vw,2.25rem); line-height: 1.16; letter-spacing: -.025em; }
    .ai-preview-copy > p { max-width: 650px; margin: 0; color: var(--text-muted); font-size: .86rem; line-height: 1.65; }
    .ai-preview-features { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 20px; }
    .ai-preview-features span { display: inline-flex; align-items: center; gap: 6px; padding: 7px 9px; border: 1px solid #dbeafe; border-radius: 8px; background: rgba(255,255,255,.78); color: #334155; font-size: .62rem; font-weight: 750; }
    .ai-preview-features i { color: #2563eb; }
    .ai-preview-note { display: block; margin-top: 15px; color: #64748b; font-size: .57rem; line-height: 1.5; }
    .ai-chat-preview { position: relative; overflow: hidden; padding: 13px; border: 1px solid #dbe3ec; border-radius: 14px; background: #fff; box-shadow: 0 20px 45px rgba(37,99,235,.1); }
    .ai-chat-head { display: flex; align-items: center; gap: 9px; padding: 3px 3px 12px; border-bottom: 1px solid #edf2f7; }
    .ai-chat-head > i { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 9px; background: #eff6ff; color: #2563eb; }
    .ai-chat-head div { min-width: 0; }
    .ai-chat-head strong,.ai-chat-head span { display: block; }
    .ai-chat-head strong { color: var(--blue-dark); font-size: .68rem; }
    .ai-chat-head span { margin-top: 2px; color: #64748b; font-size: .52rem; }
    .ai-chat-head b { margin-left: auto; padding: 5px 7px; border-radius: 99px; background: #fff7ed; color: #c2410c; font-size: .47rem; text-transform: uppercase; }
    .ai-chat-body { display: grid; gap: 9px; padding: 14px 2px 4px; }
    .ai-message { max-width: 88%; padding: 9px 11px; border-radius: 10px; color: #475569; font-size: .59rem; line-height: 1.5; }
    .ai-message.user { justify-self: end; border-bottom-right-radius: 3px; background: #102a67; color: #fff; }
    .ai-message.bot { border: 1px solid #dbeafe; border-bottom-left-radius: 3px; background: #f8fbff; }
    .ai-chat-input { display: grid; grid-template-columns: 1fr 32px; gap: 7px; margin-top: 10px; padding-top: 11px; border-top: 1px solid #edf2f7; }
    .ai-chat-input span { display: flex; align-items: center; padding: 0 9px; border: 1px solid #dbe3ec; border-radius: 8px; background: #f8fafc; color: #94a3b8; font-size: .55rem; }
    .ai-chat-input i { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 8px; background: #ff7a00; color: #fff; font-size: .62rem; }
    @media(max-width:900px){.ai-preview-shell{grid-template-columns:1fr;gap:30px;padding:34px}.ai-chat-preview{width:min(620px,100%)}}
    @media(max-width:560px){.ai-preview-section{padding:52px 14px}.ai-preview-shell{padding:22px 18px;border-radius:14px}.ai-preview-copy h2{font-size:1.55rem}.ai-preview-features{display:grid;grid-template-columns:1fr 1fr}.ai-preview-features span{min-width:0}.ai-chat-preview{padding:10px}}
  </style>
</head>
<body>
    <?php include_once __DIR__ . '/components/public_page_loader.php'; ?>

<div class="page-wrapper">
  <div class="bg-gradient"></div>
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <?php include 'components/public_header.php'; ?>

  <main>
    <section class="hero">
      <div class="hero-grid">
        <div class="fade-in-up delay-1">
          <span class="hero-tag">Cumplimiento Resolución 0312</span>
          <h1 class="hero-title">
            Automatiza tu<br>
            Gestión de <span>SST</span>
          </h1>
          <p class="hero-text">
            La plataforma definitiva para gestionar riesgos, organizar trabajadores, firmar actas digitalmente y mantener tu empresa al 100% de cumplimiento.
          </p>
          <div class="hero-actions">
            <a href="register.php" class="btn-primary">
              Regístrate ahora
              <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="login.php" class="btn-secondary">
              <i class="fa-solid fa-right-to-bracket"></i>
              Iniciar Sesión
            </a>
            <a href="demo" class="btn-secondary">
              <i class="fa-solid fa-play"></i>
              Probar demo PEM
            </a>
          </div>
        </div>

        <div class="visual-container fade-in-up delay-2">
            <div class="phone-mockup">
                <div class="phone-notch"></div>
                <div class="phone-screen">
                    <div class="mock-header">
                        <div class="mock-avatar"></div>
                        <div class="mock-lines">
                            <div class="mock-line-1"></div>
                            <div class="mock-line-2"></div>
                        </div>
                    </div>
                    <div class="mock-chart">
                        <div class="mock-bar b1"></div>
                        <div class="mock-bar b2"></div>
                        <div class="mock-bar b3"></div>
                    </div>
                    <div class="mock-list">
                        <div class="mock-item m-1"></div>
                        <div class="mock-item m-2"></div>
                        <div class="mock-item m-3"></div>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="fa-solid fa-file-pdf"></i><span>Normatividad 0312</span></div>
            <div class="floating-badge badge-2"><i class="fa-solid fa-signature"></i><span>Firmas Digitales</span></div>
            <div class="floating-badge badge-3"><i class="fa-solid fa-users"></i><span>Control Personal</span></div>
            <div class="floating-badge badge-4"><i class="fa-solid fa-chart-pie"></i><span>Reportes en Vivo</span></div>
            <div class="floating-badge badge-5"><i class="fa-solid fa-shield-virus"></i><span>Matriz GTC 45</span></div>
        </div>
      </div>
    </section>

    <section id="caracteristicas" class="features fade-in-up delay-3">
        <div class="section-header">
            <h2>Diseñado para el cumplimiento total</h2>
            <p>Nuestra plataforma integra todas las herramientas que necesitas para operar de manera sincronizada y sin dolores de cabeza.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <i class="fa-solid fa-file-pdf watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <h3>Documentos en 1 Clic</h3>
                <p>Crea actas de nombramiento y políticas corporativas listas para firmar digitalmente en segundos.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-bell watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-bell"></i></div>
                <h3>Alertas y Seguimiento</h3>
                <p>Monitorea y mantén actualizado el registro en todos los estándares mínimos para estar siempre al día.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-shield-virus watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-shield-virus"></i></div>
                <h3>Matriz de Riesgos</h3>
                <p>Identifica peligros, valora riesgos y asigna controles efectivos alineados a la normatividad GTC 45.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-users-gear watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-users-gear"></i></div>
                <h3>Control de Personal</h3>
                <p>Agrupa trabajadores por áreas, gestiona estados y automatiza encuestas sociodemográficas.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-signature watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-signature"></i></div>
                <h3>Firmas Digitales</h3>
                <p>Recolecta firmas legalmente válidas enviando un enlace directamente al celular del trabajador.</p>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-chart-line watermark-icon"></i>
                <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Reportes en Vivo</h3>
                <p>Genera métricas en tiempo real de accidentalidad, ausentismo y cumplimiento normativo de tu empresa.</p>
            </div>
        </div>
    </section>

    <section id="demo-pem" class="demo-sales-section">
        <i class="fa-solid fa-shield-halved demo-sales-watermark"></i>
        <div class="demo-sales-inner">
            <div class="demo-sales-copy">
                <span class="demo-sales-kicker"><i class="fa-solid fa-rocket"></i> Recorrido interactivo</span>
                <h2>Explora PreventWork antes de elegir tu membresía</h2>
                <p>Conoce el flujo completo del plan PEM en una empresa ficticia. Revisa el panel, el personal y los siete estándares mínimos sin afectar datos reales.</p>
                <div class="demo-sales-benefits">
                    <span><i class="fa-solid fa-circle-check"></i> Dashboard ejecutivo, alertas e indicadores</span>
                    <span><i class="fa-solid fa-circle-check"></i> Navegación por los estándares 1 al 7</span>
                    <span><i class="fa-solid fa-circle-check"></i> Ejemplos de evidencias, riesgos, exámenes y controles</span>
                </div>
                <div class="demo-sales-actions">
                    <a href="demo" class="btn-primary">Iniciar demo PEM <i class="fa-solid fa-arrow-right"></i></a>
                    <span class="demo-sales-note">Acceso privado previa aprobación · Datos ficticios</span>
                </div>
            </div>

            <div class="demo-product-window" aria-hidden="true">
                <div class="demo-window-top"><i></i><i></i><i></i></div>
                <div class="demo-window-body">
                    <div class="demo-window-sidebar">
                        <div class="demo-window-logo"></div>
                        <div class="demo-window-link active"><i class="fa-solid fa-table-cells-large"></i> Panel</div>
                        <div class="demo-window-link"><i class="fa-solid fa-users"></i> Personal</div>
                        <div class="demo-window-link"><i class="fa-solid fa-file-lines"></i> Estándar 1</div>
                        <div class="demo-window-link"><i class="fa-solid fa-graduation-cap"></i> Estándar 3</div>
                        <div class="demo-window-link"><i class="fa-solid fa-notes-medical"></i> Estándar 5</div>
                        <div class="demo-window-link"><i class="fa-solid fa-triangle-exclamation"></i> Estándar 6</div>
                        <div class="demo-window-link"><i class="fa-solid fa-shield-halved"></i> Estándar 7</div>
                    </div>
                    <div class="demo-window-content">
                        <div class="demo-window-header"><strong>Panel ejecutivo PEM</strong><span>RESPONSABLE SST</span></div>
                        <div class="demo-window-metrics">
                            <div class="demo-window-metric"><span>Cumplimiento</span><strong>84%</strong><i class="fa-solid fa-chart-line"></i></div>
                            <div class="demo-window-metric"><span>Trabajadores</span><strong>24</strong><i class="fa-solid fa-users"></i></div>
                            <div class="demo-window-metric"><span>Evidencias</span><strong>126</strong><i class="fa-solid fa-folder-open"></i></div>
                            <div class="demo-window-metric"><span>Alertas</span><strong>5</strong><i class="fa-solid fa-bell"></i></div>
                        </div>
                        <div class="demo-window-panel"><strong>Estado de los estándares PEM</strong>
                            <div class="demo-window-row"><i class="fa-solid fa-user-shield"></i><div><strong>Asignación del responsable</strong><span>Documentación completa</span></div><b>100%</b></div>
                            <div class="demo-window-row"><i class="fa-solid fa-notes-medical"></i><div><strong>Evaluaciones médicas</strong><span>4 exámenes programados</span></div><b>79%</b></div>
                            <div class="demo-window-row"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Matriz de peligros</strong><span>18 peligros identificados</span></div><b>86%</b></div>
                            <div class="demo-window-row"><i class="fa-solid fa-shield-halved"></i><div><strong>Medidas de control</strong><span>14 controles activos</span></div><b>77%</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ai-preview-section" aria-labelledby="preventwork-ai-title">
        <div class="ai-preview-shell">
            <i class="fa-solid fa-brain ai-preview-watermark" aria-hidden="true"></i>
            <div class="ai-preview-copy">
                <span class="ai-preview-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Próximamente</span>
                <h2 id="preventwork-ai-title">Consulta tu gestión SG-SST con PreventWork IA</h2>
                <p>Estamos preparando un asistente integrado para ayudarte a encontrar información del sistema, entender indicadores, ubicar evidencias y detectar asuntos que requieren seguimiento, sin salir de PreventWork.</p>
                <div class="ai-preview-features">
                    <span><i class="fa-solid fa-magnifying-glass"></i> Consultas rápidas</span>
                    <span><i class="fa-solid fa-chart-line"></i> Lectura de indicadores</span>
                    <span><i class="fa-solid fa-folder-open"></i> Ubicación de evidencias</span>
                    <span><i class="fa-solid fa-bell"></i> Alertas y pendientes</span>
                </div>
                <small class="ai-preview-note">Funcionalidad en desarrollo. Será una herramienta de apoyo y no reemplazará el criterio del profesional SST ni la asesoría legal.</small>
            </div>
            <div class="ai-chat-preview" aria-label="Vista previa conceptual de PreventWork IA">
                <div class="ai-chat-head"><i class="fa-solid fa-sparkles"></i><div><strong>PreventWork IA</strong><span>Asistente para tu gestión</span></div><b>Próximamente</b></div>
                <div class="ai-chat-body">
                    <div class="ai-message user">¿Qué actividades del plan anual necesitan seguimiento?</div>
                    <div class="ai-message bot">Te ayudaré a identificar responsables, fechas próximas y soportes pendientes en un solo resumen.</div>
                </div>
                <div class="ai-chat-input"><span>Escribe una consulta sobre tu SG-SST...</span><i class="fa-solid fa-arrow-up"></i></div>
            </div>
        </div>
    </section>

    <section id="planes" class="pricing-section fade-in-up delay-4">
        <div class="section-header">
            <h2>Planes diseñados para tu crecimiento</h2>
            <p>Empieza a gestionar tu sistema SST hoy mismo. Sin contratos forzosos, cancela cuando quieras.</p>
        </div>

        <div class="pricing-grid">
            <?php foreach ($planes_info as $plan): 
                $tiene_descuento = ($plan['precio_descuento'] > 0 && $plan['precio_descuento'] < $plan['precio_normal']);
                $precio_final = $tiene_descuento ? $plan['precio_descuento'] : $plan['precio_normal'];
                
                // Ícono y color de marca de agua dinámicos
                $watermark_icon = 'fa-paper-plane';
                $watermark_color = 'watermark-blue';
                if (stripos(strtolower($plan['nombre']), 'pro') !== false) {
                    $watermark_icon = 'fa-rocket';
                    $watermark_color = 'watermark-orange';
                } elseif (stripos(strtolower($plan['nombre']), 'enterprise') !== false) {
                    $watermark_icon = 'fa-crown';
                    $watermark_color = 'watermark-purple';
                }
            ?>
                <div class="plan-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                    
                    <?php if ($plan['popular']): ?>
                        <div class="popular-badge">Más Popular</div>
                    <?php endif; ?>

                    <div class="plan-card-inner">
                        <i class="fa-solid <?php echo $watermark_icon; ?> plan-watermark <?php echo $watermark_color; ?>"></i>
                    </div>
                    
                    <div class="plan-content-wrapper">
                        <div class="plan-header">
                            <h3 class="plan-title"><?php echo htmlspecialchars($plan['nombre']); ?></h3>
                            <div class="price-container">
                                <?php if ($tiene_descuento): ?>
                                    <p class="plan-price-old">$<?php echo number_format($plan['precio_normal'], 0, ',', '.'); ?></p>
                                <?php else: ?>
                                    <p class="plan-price-old" style="visibility:hidden;">$0</p>
                                <?php endif; ?>
                                
                                <p class="plan-price">$<?php echo number_format($precio_final, 0, ',', '.'); ?><span>/anual</span></p>
                            </div>
                        </div>
                        
                        <ul class="plan-features">
                            <li>
                                <i class="fa-solid fa-check"></i>
                                <?php echo $plan['trabajadores'] == 999 ? 'Trabajadores Ilimitados' : 'Hasta ' . $plan['trabajadores'] . ' Trabajadores'; ?>
                            </li>

                            <?php foreach ($plan['features'] as $f): ?>
                                <?php if ($f['incluido']): ?>
                                    <li><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($f['texto']); ?></li>
                                <?php else: ?>
                                    <li class="disabled"><i class="fa-solid fa-xmark"></i> <?php echo htmlspecialchars($f['texto']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        
                        <a href="register.php?plan=<?php echo $plan['id']; ?>" class="btn-plan-select <?php echo $plan['popular'] ? 'btn-plan-solid' : 'btn-plan-outline'; ?>">
                            Elegir Plan
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="landing-band process-band">
        <div class="landing-inner">
            <div class="landing-heading">
                <div class="landing-kicker">Una ruta clara</div>
                <h2>Del registro de tu empresa al seguimiento diario del SG-SST</h2>
                <p>PreventWork conecta la información de la empresa, las personas responsables y las evidencias de cumplimiento en un solo flujo de trabajo.</p>
            </div>
            <div class="process-grid">
                <div class="process-step">
                    <div class="process-number">01</div>
                    <div class="process-icon"><i class="fa-solid fa-building"></i></div>
                    <h3>Registra tu empresa</h3>
                    <p>Selecciona el alcance adecuado y completa los datos legales de la organización.</p>
                </div>
                <div class="process-step">
                    <div class="process-number">02</div>
                    <div class="process-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <h3>Vincula tu equipo</h3>
                    <p>Asocia al representante, responsable SST y trabajadores bajo la misma empresa.</p>
                </div>
                <div class="process-step">
                    <div class="process-number">03</div>
                    <div class="process-icon"><i class="fa-solid fa-list-check"></i></div>
                    <h3>Gestiona estándares</h3>
                    <p>Organiza actas, planillas, capacitaciones, firmas y soportes por cada requisito.</p>
                </div>
                <div class="process-step">
                    <div class="process-number">04</div>
                    <div class="process-icon"><i class="fa-solid fa-chart-column"></i></div>
                    <h3>Haz seguimiento</h3>
                    <p>Consulta estados, vencimientos, asistencia y avances desde un panel central.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-band roles-band">
        <div class="landing-inner roles-layout">
            <div class="roles-copy">
                <div class="landing-kicker">Trabajo coordinado</div>
                <h2>Cada persona ve lo que necesita para cumplir su función</h2>
                <p>Los roles mantienen las responsabilidades claras, evitan cruces de información y permiten que cada proceso avance con la persona correcta.</p>
                <a href="register.php">Crear una cuenta <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="role-list">
                <div class="role-row">
                    <div class="role-row-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                    <div><h3>Representante Legal</h3><p>Administra la empresa, aprueba documentos, firma actas y consulta el plan contratado.</p></div>
                </div>
                <div class="role-row">
                    <div class="role-row-icon"><i class="fa-solid fa-helmet-safety"></i></div>
                    <div><h3>Responsable SG-SST</h3><p>Programa actividades, gestiona evidencias y lidera el cumplimiento de los estándares.</p></div>
                </div>
                <div class="role-row">
                    <div class="role-row-icon"><i class="fa-solid fa-id-badge"></i></div>
                    <div><h3>Trabajador</h3><p>Completa su información, participa en capacitaciones y registra asistencia con firma.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-band evidence-band">
        <div class="landing-inner evidence-layout">
            <div>
                <div class="landing-heading">
                    <div class="landing-kicker">Evidencia organizada</div>
                    <h2>La operación deja de estar dispersa entre archivos y mensajes</h2>
                    <p>Cada acción relevante queda asociada a la empresa y disponible para seguimiento.</p>
                </div>
                <div class="evidence-list">
                    <div class="evidence-item"><i class="fa-solid fa-check"></i><h3>Actas firmadas</h3><p>Versiones y PDF final del documento de designación.</p></div>
                    <div class="evidence-item"><i class="fa-solid fa-check"></i><h3>Planillas PILA</h3><p>Soportes mensuales organizados por empresa y año.</p></div>
                    <div class="evidence-item"><i class="fa-solid fa-check"></i><h3>Capacitaciones</h3><p>Programación, participantes, estados y reprogramaciones.</p></div>
                    <div class="evidence-item"><i class="fa-solid fa-check"></i><h3>Asistencia</h3><p>Registros digitales con identificación y firma del trabajador.</p></div>
                </div>
            </div>
            <div class="compliance-panel">
                <i class="fa-solid fa-shield-halved compliance-watermark"></i>
                <h2>Construido alrededor de la Resolución 0312 de 2019</h2>
                <p>El alcance de cada membresía acompaña el tamaño y las necesidades de la empresa, desde los estándares esenciales hasta una gestión más amplia del sistema.</p>
                <a href="#planes">Comparar membresías <i class="fa-solid fa-arrow-up"></i></a>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="final-cta-inner">
            <div class="final-cta-copy">
                <div class="final-cta-kicker"><i class="fa-solid fa-helmet-safety"></i> Gestión confiable</div>
                <h2>Empieza a ordenar la gestión SST de tu empresa</h2>
                <p>Elige una membresía, registra tu organización y continúa al proceso de activación.</p>
                <a href="register.php" class="btn-primary">Crear mi empresa <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

  </main>

  <?php include 'components/public_footer.php'; ?>
  <?php include 'components/floating_buttons.php'; ?>

</div>

</body>
</html>
