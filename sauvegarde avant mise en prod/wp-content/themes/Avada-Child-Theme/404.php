<?php
/**
 * Template 404 - Page d'erreur personnalisée
 * 
 * @package Avada-Child-Theme
 */

get_header(); ?>

<div id="awan"></div>

<style>
/* ==========================================================================
   PAGE 404 - STYLES PERSONNALISÉS
   ========================================================================== */
/* Fond de nuages défilants */
.error404 #main{
        background: #f6efe6;
}
#awan {
    position: absolute;
    top: 160px;
    left: 0px;
    width: 100%;
    height: 100%;
    text-align: center;
    margin: 0px;
    padding: 0px;
    background-color: #C0DEED;
    background: url(https://abs.twimg.com/images/themes/theme1/bg.png) center top repeat-x;
    padding-top: 100px;
    padding-bottom: 10px;
    width: 100%;
    height: 100%;
    min-height: 100vh;
    animation: awan-animasi 20s linear infinite;
    -ms-animation: awan-animasi 20s linear infinite;
    -moz-animation: awan-animasi 20s linear infinite;
    -webkit-animation: awan-animasi 20s linear infinite;
    z-index: 0;
    pointer-events: none;
}
.bloc404 {
    display: inline-flex;
    justify-content: space-between;
    align-items: flex-start;
}
@keyframes awan-animasi {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 0;
    }
}

@-webkit-keyframes awan-animasi {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 0;
    }
}

@-ms-keyframes awan-animasi {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 0;
    }
}

@-moz-keyframes awan-animasi {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 0;
    }
}

.page-404-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0px 20px;
    background: transparent;
    position: relative;
    overflow: visible;
    z-index: 2;
    margin-top: 160px;
    top: -18vh;
}

.page-404-container::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(222, 91, 9, 0.05) 0%, transparent 70%);
    border-radius: 50%;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

.page-404-content {
    max-width: 1200px;
    width: 100%;
    text-align: center;
    position: relative;
    z-index: 1;
}

.error-404-main-content {
    display: flex;
    align-items: center;
    gap: 60px;
    margin-top: 40px;
}

@media (max-width: 768px) {
    .error-404-main-content {
        flex-direction: column;
        text-align: center;
    }
    .error-404-title {
    font-size: 80px !important;
}
    .bloc404 {
    display: block;

}
.page-404-container {
    top: -13vh;
}
}

/* Animation du voyageur perdu */
.traveler-animation {
    margin-bottom: 40px;
    position: relative;
    height: 164px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    z-index: 3;
}

.traveler-icon {
    font-size: 120px;
    animation: bounce 2s ease-in-out infinite;
    position: relative;
    z-index: 2;
}

.traveler-icon::before {
    content: '🧳';
    display: block;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25% { transform: translateY(-20px) rotate(-5deg); }
    50% { transform: translateY(-10px) rotate(0deg); }
    75% { transform: translateY(-20px) rotate(5deg); }
}

/* Oiseaux volants SVG */
.bird {
    background-image: url('https://s3-us-west-2.amazonaws.com/s.cdpn.io/174479/bird-cells-new.svg');
    filter: brightness(0);
    background-size: auto 100%;
    width: 88px;
    height: 125px;
    will-change: background-position;
    animation-name: fly-cycle;
    animation-timing-function: steps(10);
    animation-iteration-count: infinite;
    opacity: 0.9;
}

.bird-one {
    animation-duration: 1s;
    animation-delay: -0.5s;
}

.bird-two {
    animation-duration: 0.9s;
    animation-delay: -0.75s;
}

.bird-three {
    animation-duration: 1.25s;
    animation-delay: -0.25s;
}

.bird-four {
    animation-duration: 1.1s;
    animation-delay: -0.5s;
}

.bird-container {
    position: absolute;
    top: 10%;
    left: -3%;
    transform: scale(0) translateX(-10vw);
    will-change: transform;
    animation-name: fly-right-one;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    z-index: 4;
}

.bird-container-one {
    animation-duration: 22s;
    animation-delay: 0;
    top: -19vh;
}

.bird-container-two {
    animation-duration: 24s;
    animation-delay: 2s;
    top: -10vh;
}

.bird-container-three {
    animation-duration: 21s;
    animation-delay: 12s;
    top: 23vh;
}

.bird-container-four {
    animation-duration: 23s;
    animation-delay: 13s;
    top: 25%;
}

@keyframes fly-cycle {
    100% {
        background-position: -900px 0;
    }
}

@keyframes fly-right-one {
    0% {
        transform: scale(0.3) translateX(-10vw);
        opacity: 0;
    }
    5% {
        opacity: 0.6;
    }
    10% {
        transform: translateY(2vh) translateX(10vw) scale(0.4);
    }
    20% {
        transform: translateY(0vh) translateX(30vw) scale(0.5);
    }
    30% {
        transform: translateY(4vh) translateX(50vw) scale(0.6);
    }
    40% {
        transform: translateY(2vh) translateX(70vw) scale(0.6);
    }
    50% {
        transform: translateY(0vh) translateX(90vw) scale(0.6);
    }
    60% {
        transform: translateY(0vh) translateX(110vw) scale(0.6);
    }
    95% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(0vh) translateX(110vw) scale(0.6);
        opacity: 0;
    }
}

/* Nuages */
.cloud {
    position: absolute;
    opacity: 0.15;
    z-index: 1;
}

.cloud::before {
    content: '☁️';
    display: block;
    font-size: 40px;
}

.cloud:nth-child(5) {
    top: 10%;
    left: -100px;
    animation: cloudMove1 80s linear infinite;
    animation-delay: 0s;
}

.cloud:nth-child(6) {
    top: 60%;
    left: -100px;
    animation: cloudMove2 90s linear infinite;
    animation-delay: 30s;
}

.cloud:nth-child(7) {
    top: 35%;
    left: -100px;
    animation: cloudMove3 100s linear infinite;
    animation-delay: 60s;
}

@keyframes cloudMove1 {
    0% { transform: translateX(0); opacity: 0; }
    10% { opacity: 0.15; }
    90% { opacity: 0.15; }
    100% { transform: translateX(calc(100vw + 200px)); opacity: 0; }
}

@keyframes cloudMove2 {
    0% { transform: translateX(0); opacity: 0; }
    10% { opacity: 0.12; }
    90% { opacity: 0.12; }
    100% { transform: translateX(calc(100vw + 200px)); opacity: 0; }
}

@keyframes cloudMove3 {
    0% { transform: translateX(0); opacity: 0; }
    10% { opacity: 0.18; }
    90% { opacity: 0.18; }
    100% { transform: translateX(calc(100vw + 200px)); opacity: 0; }
}

/* Titre et message */
.error-404-left {
    flex: 0 0 auto;
    text-align: left;
    padding-right: 40px;
}

.error-404-right {
    flex: 1;
    text-align: left;
    padding-left: 60px;
    border-left: 3px solid #de5b09;
}

@media (max-width: 768px) {
    .error-404-left {
        text-align: center;
    }
    
    .error-404-right {
        text-align: center;
        padding-left: 0;
        border-left: none;
        border-top: 3px solid #de5b09;
        padding-top: 40px;
        margin-top: 40px;
    }
}

.error-404-title {
    font-size: 120px;
    font-weight: 900;
    color: #de5b09;
    margin: 0;
    line-height: 1;
    text-shadow: 3px 3px 0 rgba(222, 91, 9, 0.1);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.error-404-subtitle {
    font-size: 32px;
    color: #333;
    margin: 20px 0 10px;
    font-weight: 600;
}

.error-404-message {
    font-size: 18px;
    color: #666;
    margin-bottom: 50px;
    max-width: 100%;
}

/* Barre de recherche */
.search-404 {
    max-width: 600px;
    margin: 0 auto 60px;
    position: relative;
}

.search-404 form {
    display: flex;
    gap: 10px;
}


/* Boutons d'action */
.action-buttons {
    display: flex;
    gap: 20px;
    justify-content: left;
    flex-wrap: wrap;
    margin-top: 50px;
}

.action-btn {
    padding: 16px 40px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.action-btn-primary {
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(222, 91, 9, 0.3);
}

.action-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(222, 91, 9, 0.4);
    color: #fff;
}

.action-btn-secondary {
    background: #fff;
    color: #de5b09;
    border: 2px solid #de5b09;
}

.action-btn-secondary:hover {
    background: #de5b09;
    color: #fff;
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 768px) {
    .error-404-title {
        font-size: 80px;
    }
    
    .error-404-subtitle {
        font-size: 24px;
    }
    
    .traveler-icon {
        font-size: 80px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Animation d'entrée */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-404-content > * {
    animation: fadeInUp 0.6s ease-out;
}

.page-404-content > *:nth-child(2) {
    animation-delay: 0.1s;
}

.page-404-content > *:nth-child(3) {
    animation-delay: 0.2s;
}

.page-404-content > *:nth-child(4) {
    animation-delay: 0.3s;
}
</style>

<div class="page-404-container">
    <div class="page-404-content">
        <!-- Animation du voyageur -->
        <div class="traveler-animation">
            <div class="bird-container bird-container-one">
                <div class="bird bird-one"></div>
            </div>
            <div class="bird-container bird-container-two">
                <div class="bird bird-two"></div>
            </div>
            <div class="bird-container bird-container-three">
                <div class="bird bird-three"></div>
            </div>
            <div class="bird-container bird-container-four">
                <div class="bird bird-four"></div>
            </div>
            <div class="cloud"></div>
            <div class="cloud"></div>
            <div class="cloud"></div>
            <div class="traveler-icon"></div>
        </div>
<div class="bloc404">
        <!-- Contenu en deux colonnes -->
        <div class="error-404-left">
            <h1 class="error-404-title">404</h1>
        </div>
        
        <div class="error-404-right">
            <h2 class="error-404-subtitle">Oups ! Vous êtes perdu ?</h2>
            <p class="error-404-message">
                Il semble que vous ayez pris un mauvais chemin. <br>Pas de panique, même les meilleurs voyageurs se perdent parfois !
            </p>
             <!-- Boutons d'action -->
            <div class="action-buttons">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="action-btn action-btn-primary">
                    Retour à l'accueil
                </a>
                <a href="<?php echo esc_url(home_url('/circuit-voyage-asie/toutes-destinations-asie/')); ?>" class="action-btn action-btn-secondary">
                    Voir nos voyages
                </a>
            </div>
        </div>
           
        </div>
    </div>
</div>

<?php get_footer(); ?>

