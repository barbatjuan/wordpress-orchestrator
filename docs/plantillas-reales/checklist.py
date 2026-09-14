"""Render checklist.json as one self-contained HTML page for the progress artifact.

Run:  python checklist.py <out.html>
The JSON is the source of truth and is committed with the work, so progress lives in git
history as well as on the published page.
"""
import html
import io
import json
import os
import sys

AQUI = os.path.dirname(os.path.abspath(__file__))
D = json.load(io.open(os.path.join(AQUI, "checklist.json"), encoding="utf-8"))
e = html.escape

ETIQUETA = {
    "hecho": "Hecho",
    "en-curso": "En curso",
    "parcial": "Parcial",
    "pendiente": "Pendiente",
    "necesita-ok": "Necesita tu OK",
    "bloqueado": "Bloqueado",
}

tareas = [t for b in D["bloques"] for t in b["tareas"]]
celdas = [v[0] for p in D["plantillas"] for v in p["estado"].values()]
unidades = [t["s"] for t in tareas] + celdas
total = len(unidades)
hechas = sum(1 for s in unidades if s == "hecho")
cuenta = {k: sum(1 for s in unidades if s == k) for k in ETIQUETA}
tuyas = [t for t in tareas if t["s"] in ("necesita-ok", "bloqueado")]
pct = round(100 * hechas / total) if total else 0


def chip(s, nota=""):
    extra = f'<span class="nota">{e(nota)}</span>' if nota else ""
    return f'<span class="chip s-{s}">{ETIQUETA[s]}</span>{extra}'


barra = "".join(
    f'<span class="seg s-{k}" style="flex:{cuenta[k]}" title="{ETIQUETA[k]}: {cuenta[k]}"></span>'
    for k in ("hecho", "en-curso", "parcial", "pendiente", "necesita-ok", "bloqueado")
    if cuenta[k]
)
leyenda = " · ".join(f"{ETIQUETA[k]} {cuenta[k]}" for k in ETIQUETA if cuenta[k])

filas_plantillas = []
for p in D["plantillas"]:
    tds = "".join(
        f'<td>{chip(*p["estado"][paso])}</td>' for paso in D["pasos"]
    )
    filas_plantillas.append(
        f'<tr><th scope="row"><b>{e(p["nombre"])}</b><span class="sub">{e(p["tipo"])} · {e(p["sector"])}</span></th>{tds}</tr>'
    )

bloques = []
for b in D["bloques"]:
    lis = "".join(
        f'<li class="t s-{t["s"]}"><span class="tid">{e(t["id"])}</span>'
        f'<span class="tt">{e(t["t"])}{("<span class=\"tn\">" + e(t["n"]) + "</span>") if t.get("n") else ""}</span>'
        f'{chip(t["s"])}</li>'
        for t in b["tareas"]
    )
    hb = sum(1 for t in b["tareas"] if t["s"] == "hecho")
    bloques.append(
        f'<section class="bloque"><h3><span class="bid">{e(b["id"])}</span>{e(b["titulo"])}'
        f'<span class="bc">{hb} de {len(b["tareas"])}</span></h3><ul>{lis}</ul></section>'
    )

tuyas_html = "".join(
    f'<li><span class="tid">{e(t["id"])}</span><span>{e(t["t"])}<span class="tn">{e(t.get("n", ""))}</span></span>{chip(t["s"])}</li>'
    for t in tuyas
)

pag = {k: " · ".join(e(x) for x in v) for k, v in D["paginas"].items()}
ya = "".join(f"<li>{e(x)}</li>" for x in D["ya_hecho"])
cab = "".join(f"<th scope=\"col\">{e(p)}</th>" for p in D["pasos"])

PAGINA = f"""<title>Checklist de plantillas reales</title>
<meta name="description" content="Lo que falta para que el orquestador use la biblioteca de plantillas">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style>
:root {{
  --suelo:#F4F3EF; --placa:#FFFFFF; --tinta:#1C1F23; --sec:#5B6068; --linea:#DAD8D1;
  --hecho:#2E6A45; --hecho-f:#E3EFE6; --curso:#1F5C8F; --curso-f:#E2ECF5;
  --parcial:#7A5A10; --parcial-f:#F3EBD7; --pend:#5B6068; --pend-f:#ECEBE6;
  --ok:#8A4A0E; --ok-f:#F6E6D6; --bloq:#9B3526; --bloq-f:#F5E1DD;
}}
@media (prefers-color-scheme: dark) {{
  :root:not([data-theme="light"]) {{
    --suelo:#141619; --placa:#1C1F23; --tinta:#E8E9EB; --sec:#A2A7AE; --linea:#30343A;
    --hecho:#7CC596; --hecho-f:#1C3024; --curso:#86B8E4; --curso-f:#1A2A38;
    --parcial:#D9B45E; --parcial-f:#332A14; --pend:#A2A7AE; --pend-f:#25282D;
    --ok:#EBA56A; --ok-f:#3A2715; --bloq:#EE8E7E; --bloq-f:#3A1E19;
  }}
}}
:root[data-theme="dark"] {{
  --suelo:#141619; --placa:#1C1F23; --tinta:#E8E9EB; --sec:#A2A7AE; --linea:#30343A;
  --hecho:#7CC596; --hecho-f:#1C3024; --curso:#86B8E4; --curso-f:#1A2A38;
  --parcial:#D9B45E; --parcial-f:#332A14; --pend:#A2A7AE; --pend-f:#25282D;
  --ok:#EBA56A; --ok-f:#3A2715; --bloq:#EE8E7E; --bloq-f:#3A1E19;
}}
* {{ box-sizing:border-box; }}
body {{ margin:0; background:var(--suelo); color:var(--tinta);
  font:15px/1.55 "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", sans-serif; }}
.mono, .tid, .bid, .bc, .chip, .nota, th[scope=col] {{ font-family:"IBM Plex Mono", ui-monospace, Consolas, monospace; }}
main {{ max-width:1080px; margin:0 auto; padding:40px 24px 80px; }}
header .kicker {{ font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:var(--sec); margin:0; }}
h1 {{ font-size:32px; font-weight:600; letter-spacing:-.015em; line-height:1.15; margin:8px 0 10px; text-wrap:balance; }}
.lead {{ color:var(--sec); max-width:68ch; margin:0; }}
.progreso {{ margin:28px 0 8px; display:flex; align-items:baseline; gap:14px; flex-wrap:wrap; }}
.progreso .num {{ font-size:40px; font-weight:600; letter-spacing:-.02em; font-variant-numeric:tabular-nums; }}
.progreso .de {{ color:var(--sec); }}
.barra {{ display:flex; height:10px; border-radius:2px; overflow:hidden; background:var(--pend-f); gap:2px; }}
.seg.s-hecho {{ background:var(--hecho); }} .seg.s-en-curso {{ background:var(--curso); }}
.seg.s-parcial {{ background:var(--parcial); }} .seg.s-pendiente {{ background:var(--linea); }}
.seg.s-necesita-ok {{ background:var(--ok); }} .seg.s-bloqueado {{ background:var(--bloq); }}
.leyenda {{ font-size:12px; color:var(--sec); margin-top:8px; font-variant-numeric:tabular-nums; }}
h2 {{ font-size:13px; letter-spacing:.08em; text-transform:uppercase; color:var(--sec); font-weight:500; margin:44px 0 14px; }}
.tuyo {{ background:var(--ok-f); border-left:3px solid var(--ok); padding:16px 18px; border-radius:2px; }}
.tuyo h2 {{ margin:0 0 10px; color:var(--ok); }}
.tuyo ul, .bloque ul {{ list-style:none; margin:0; padding:0; }}
.tuyo li, .bloque li {{ display:grid; grid-template-columns:44px 1fr auto; gap:12px; align-items:baseline; padding:9px 0; border-top:1px solid var(--linea); }}
.tuyo li:first-child {{ border-top:0; }}
.tid {{ font-size:12px; color:var(--sec); }}
.tn {{ display:block; font-size:13px; color:var(--sec); margin-top:2px; }}
.chip {{ display:inline-block; font-size:11px; padding:2px 8px; border-radius:2px; white-space:nowrap; }}
.chip.s-hecho {{ color:var(--hecho); background:var(--hecho-f); }}
.chip.s-en-curso {{ color:var(--curso); background:var(--curso-f); }}
.chip.s-parcial {{ color:var(--parcial); background:var(--parcial-f); }}
.chip.s-pendiente {{ color:var(--pend); background:var(--pend-f); }}
.chip.s-necesita-ok {{ color:var(--ok); background:var(--ok-f); }}
.chip.s-bloqueado {{ color:var(--bloq); background:var(--bloq-f); }}
.nota {{ display:block; font-size:11px; color:var(--sec); margin-top:4px; }}
.defin {{ background:var(--placa); border:1px solid var(--linea); padding:16px 18px; border-radius:2px; }}
.defin p {{ margin:0; max-width:78ch; }}
.tabla {{ overflow-x:auto; background:var(--placa); border:1px solid var(--linea); border-radius:2px; }}
table {{ border-collapse:collapse; width:100%; min-width:820px; }}
th, td {{ text-align:left; vertical-align:top; padding:10px 12px; border-top:1px solid var(--linea); }}
thead th {{ border-top:0; font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--sec); font-weight:500; }}
tbody th b {{ display:block; font-weight:600; }}
.sub {{ display:block; font-size:12px; color:var(--sec); }}
.bloques {{ display:grid; grid-template-columns:1fr; gap:14px; }}
.bloque {{ background:var(--placa); border:1px solid var(--linea); padding:14px 18px 6px; border-radius:2px; }}
.bloque h3 {{ display:flex; align-items:baseline; gap:10px; font-size:16px; font-weight:600; margin:0 0 6px; }}
.bid {{ font-size:12px; color:var(--sec); }}
.bc {{ margin-left:auto; font-size:12px; color:var(--sec); font-weight:400; font-variant-numeric:tabular-nums; }}
.bloque li.s-hecho .tt {{ color:var(--sec); text-decoration:line-through; text-decoration-color:var(--linea); }}
.dos {{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }}
.dos > div {{ background:var(--placa); border:1px solid var(--linea); padding:14px 18px; border-radius:2px; }}
.dos h4 {{ margin:0 0 6px; font-size:13px; font-weight:600; }}
.dos p {{ margin:0; font-size:14px; color:var(--sec); }}
.ya ul {{ margin:0; padding-left:18px; color:var(--sec); }}
@media (max-width:720px) {{ .dos {{ grid-template-columns:1fr; }} h1 {{ font-size:26px; }} }}
</style>

<main>
<header>
  <p class="kicker">feat/plantillas-reales · actualizado {e(D["actualizado"])}</p>
  <h1>Checklist de plantillas reales</h1>
  <p class="lead">Lo que falta para que el orquestador use la biblioteca. Cada marca se cambia en cuanto la tarea está terminada y verificada, no antes.</p>
  <div class="progreso"><span class="num">{hechas}</span><span class="de">de {total} marcas hechas · {pct}%</span></div>
  <div class="barra" role="img" aria-label="{leyenda}">{barra}</div>
  <div class="leyenda">{leyenda}</div>
</header>

<section class="tuyo" style="margin-top:32px">
  <h2>Lo que depende de ti</h2>
  <ul>{tuyas_html}</ul>
</section>

<h2>Terminado quiere decir</h2>
<div class="defin"><p>{e(D["terminado_es"])}</p></div>

<h2>Las {len(D["plantillas"])} plantillas</h2>
<div class="tabla"><table>
<thead><tr><th scope="col">Plantilla</th>{cab}</tr></thead>
<tbody>{"".join(filas_plantillas)}</tbody>
</table></div>

<h2>Páginas de cada tipo</h2>
<div class="dos">
  <div><h4>Corporate · {len(D["paginas"]["corporate"])}</h4><p>{pag["corporate"]}</p></div>
  <div><h4>Ecommerce · {len(D["paginas"]["ecommerce"])}</h4><p>{pag["ecommerce"]}</p></div>
</div>
<div class="defin" style="margin-top:14px"><p><b>Alcance:</b> {e(D["fuera"])}</p></div>

<h2>Tareas</h2>
<div class="bloques">{"".join(bloques)}</div>

<h2>Ya hecho antes de empezar</h2>
<div class="defin ya"><ul>{ya}</ul></div>
</main>
"""

io.open(sys.argv[1], "w", encoding="utf-8", newline="\n").write(PAGINA)
print(f"{hechas}/{total} hechas → {sys.argv[1]}")
