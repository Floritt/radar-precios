<?php /** Dibujo de ejemplo de un historial de precios: la línea baja a escalones y el punto amarillo marca el mínimo. */ ?>
<div class="hero__chart" aria-hidden="true">
  <svg viewBox="0 0 520 330" role="presentation" focusable="false">
    <g stroke="rgba(255,255,255,.2)" stroke-width="1" stroke-dasharray="2 7" stroke-linecap="round">
      <path d="M46 60H514M46 130H514M46 200H514M46 270H514"/>
    </g>
    <g fill="rgba(255,255,255,.72)" font-size="13">
      <text x="0" y="64">50 €</text><text x="0" y="134">40 €</text><text x="0" y="204">30 €</text><text x="0" y="274">20 €</text>
      <text x="96" y="302">septiembre</text><text x="258" y="302">octubre</text><text x="404" y="302">noviembre</text>
    </g>
    <path class="hc-area" d="M52 95H124V81H176V109H240V95H292V130H346V116H392V165H432V201H474V137H512V270H52Z" fill="rgba(255,255,255,.1)"/>
    <path class="hc-line" pathLength="1" d="M52 95H124V81H176V109H240V95H292V130H346V116H392V165H432V201H474V137H512" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    <g class="hc-dot">
      <circle cx="453" cy="201" r="19" fill="rgba(255,212,59,.28)"/>
      <circle cx="453" cy="201" r="10" fill="#FFD43B" stroke="#1F3FD8" stroke-width="3"/>
    </g>
    <g class="hc-tag">
      <path d="M447 226l6-8 6 8z" fill="#FFD43B"/>
      <rect x="364" y="225" width="150" height="36" rx="10" fill="#FFD43B"/>
      <text x="439" y="248.5" text-anchor="middle" font-size="15.5" font-weight="700" fill="#10162F">Mínimo: 29,90 €</text>
    </g>
    <text x="54" y="72" font-size="14.5" font-weight="700" fill="#fff">44,99 €</text>
  </svg>
  <p class="hero__caption">El punto amarillo marca el mejor momento para comprar.</p>
</div>
