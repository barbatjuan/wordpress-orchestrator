# Font manifest

The typefaces every mockup in this skill NAMES, the bytes that make them render,
and the licence that lets this repository carry them.

## Why this file exists

Until these files landed, every mockup here named real families and shipped none
of them. `--font-primary: 'Fraunces', Georgia, 'Times New Roman', serif` renders
**Georgia** on any machine without Fraunces installed — which is every machine
that has not deliberately installed six Google families. Checked against the OS
font collection on the machine this was found on: 211 families installed, and not
one of the six.

So the EDITORIAL anchor was being judged as Georgia, DIRECT as Arial Black, and
LUXE and INSTITUTIONAL as whatever `system-ui` resolves to. Every visual verdict
anyone had reached about this framework — including "these don't feel premium" —
was a verdict on the fallback stack. No craft layer rescues the wrong typeface.

**Do not test this with `document.fonts.check()`.** It returns `true` for almost
any family name and proves nothing; that API is how the defect stayed invisible.
Ask the operating system, or measure rendered glyph metrics.

## Why embedding is not the thing the old comments refused

The four mockups used to carry a sentence saying the families were *"NAMED with
honest system fallbacks and never `@font-face`'d at a URL: the Artifact CSP blocks
external requests, and a blocked font is worse than a declared fallback."*

The premise was right and the conclusion did not follow. A `data:` URI issues no
request, so there is nothing for a CSP to block. The reasoning ruled out
`url(https://…)` and was read as ruling out `@font-face` itself.
`specs/2026-08-14-perceptual-axes-design.md` already prescribes exactly this
embedding. Those comments now say what is true, because a comment describing a
decision that has been reversed sends the next reader to re-derive it wrongly.

## Why the latin subset is enough

Google serves one woff2 per `unicode-range`. The **latin** file covers
`U+0000-00FF` plus punctuation, currency and a few marks — which is the whole of
Spanish: `á é í ó ú ñ ü ¿ ¡` all live under `U+00FF`, and Spanish is the language
every one of these mockups is written in. The `latin-ext`, `vietnamese`,
`cyrillic` and `greek` subsets are not fetched, not committed and not embedded.

## Why Archivo is two files

`Archivo Expanded` is **not a separate family**: it is Archivo at `wdth` 125.
The obvious embedding — one full-range `62..125` file under two `@font-face`
names — costs 90,104 bytes *and* has to be base64'd twice in any file that uses
both widths, because a `data:` URI is inline and cannot be shared between two
rules. Google will serve a **pinned width instance** instead, and two of those
are smaller than one shared full-range file:

| what | bytes |
|---|---|
| `Archivo:wdth,wght@62..125,400..700` (full range, would need duplicating) | 90,104 |
| `Archivo:wdth,wght@100,400..700` + `Archivo:wdth,wght@125,400..700` | 69,636 |

The `font-stretch` descriptor is what makes the expanded face render expanded: an
element asking for the default `normal` (100%) is clamped into the face's declared
range, so a face declaring `125%` renders at 125% without every rule saying so.
Measured in a browser: Archivo 1287.91px advance, Archivo Expanded 1631.81px —
**ratio 1.267**. Get this wrong and the DIRECT anchor renders as plain Archivo,
which is a different design.

## Weights are declared honestly

The `weight` column is each face's **true** range, never a convenient one.
Declaring a range wider than the font holds would suppress the browser's synthetic
bold and hide a mockup asking for a weight nobody drew.

Instrument Serif is the case in point: it is a single-weight display serif, and
`ecommerce-mockup.html` asked its headings for `font-weight: 600`. Chrome answered
with **synthetic bold** — 1.61× the ink of the 400, with 600 and 700 producing
*identical* output, which is the giveaway, since a real weight axis is graduated
and a synthesised one is on/off. Note that the advance width is unchanged, so a
width measurement detects none of this. That mockup now asks for the 400 it has.

## Licence — verified per family, not assumed

All fourteen families are **SIL Open Font License 1.1**. That was checked rather than
believed, through three independent signals per family:

1. the family's directory in `google/fonts` upstream is `ofl/` — that repository
   segregates by licence, so the path *is* a claim (`apache/` and `ufl/` exist and
   neither holds any of these);
2. its `METADATA.pb` there declares `license: "OFL"`;
3. the committed `OFL.txt` carries the literal header
   `SIL OPEN FONT LICENSE Version 1.1`.

OFL permits redistribution **only with the licence text accompanying the fonts**,
so each family's `OFL.txt` is committed in this directory beside its `woff2`.
This repository is public under Apache-2.0 and its `LICENSE` hands every reader
the right to redistribute what it contains — a right we can only grant over what
we actually hold. It is the same reasoning that keeps commercial theme kits out of here.

**The files are Google's own subsets, byte-for-byte as served, and are not
re-subset here.** That is deliberate: Source Sans 3 carries the Reserved Font Name
*'Source'*, and OFL §3 restricts an RFN in **modified** versions. Redistributing
an unmodified file under its original name is what OFL §2 permits outright, so
not touching the bytes is what keeps that clause satisfied. IBM Plex Mono is the
same case: its notice reserves the name *'Plex'*, and it is shipped unmodified. If anyone ever
re-subsets these, that analysis has to be redone before the result is committed.

## The set

Every file below is the `latin` subset served by `fonts.googleapis.com/css2` to a
current-Chrome user agent. Fetch with an old or absent user agent and Google
serves TTF instead, which is roughly four times the size and not what these rows
describe. `sha256` is the committed file, so drift is detectable rather than
assumed.

| Family | File | Axes served | Weight | Licence | sha256 (first 16) | Bytes |
|---|---|---|---|---|---|---|
| Fraunces | `fraunces-latin.woff2` | `opsz 9..144`, `wght 400..700` | 400 700 | SIL OFL 1.1 | `7234ed860a9cc830` | 67,304 |
| Instrument Serif | `instrument-serif-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `5eb09b5ac0e28b67` | 21,032 |
| Inter Tight | `inter-tight-latin.woff2` | `wght 400..700` | 400 700 | SIL OFL 1.1 | `77fefe8ca19b9f69` | 44,872 |
| DM Sans | `dm-sans-latin.woff2` | `opsz 9..40`, `wght 400..700` | 400 700 | SIL OFL 1.1 | `ca72d2bcea8f4daa` | 62,724 |
| Source Sans 3 | `source-sans-3-latin.woff2` | `wght 400..700` | 400 700 | SIL OFL 1.1 | `7a19a7027e125257` | 28,740 |
| Archivo | `archivo-latin.woff2` | `wdth 100` (pinned), `wght 400..700` | 400 700 | SIL OFL 1.1 | `8f704806dbedeaae` | 34,928 |
| Archivo Expanded | `archivo-expanded-latin.woff2` | `wdth 125` (pinned), `wght 400..700` | 400 700 | SIL OFL 1.1 | `8ac503c4c5897b58` | 34,708 |
| Bodoni Moda | `bodoni-moda-latin.woff2` | `opsz 6..96`, `wght 400` | 400 | SIL OFL 1.1 | `2bd498670e726062` | 26,688 |
| Bodoni Moda *italic* | `bodoni-moda-italic-latin.woff2` | `opsz 6..96`, `wght 400` | 400 | SIL OFL 1.1 | `2ad6213c0ab5438a` | 29,644 |
| Jost | `jost-latin.woff2` | `wght 300..500` | 300 500 | SIL OFL 1.1 | `7726a5cd6f3c0e87` | 26,576 |
| Newsreader | `newsreader-latin.woff2` | `opsz 6..72`, `wght 200..500` | 200 500 | SIL OFL 1.1 | `6e4f2958c3a7c4a8` | 132,000 |
| Newsreader *italic* | `newsreader-italic-latin.woff2` | `opsz 6..72`, `wght 200..500` | 200 500 | SIL OFL 1.1 | `5dfcd10d24af8c82` | 146,872 |
| Schibsted Grotesk | `schibsted-grotesk-latin.woff2` | `wght 400..500` | 400 500 | SIL OFL 1.1 | `4c8b93f431d462c6` | 46,752 |
| IBM Plex Mono 400 | `ibm-plex-mono-400-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `08949f728dc52d52` | 14,708 |
| IBM Plex Mono 500 | `ibm-plex-mono-500-latin.woff2` | none (static) | 500 | SIL OFL 1.1 | `01d285447409c8a5` | 14,888 |
| Instrument Sans | `instrument-sans-latin.woff2` | `wght 400..600` | 400 600 | SIL OFL 1.1 | `2ee17598a98d8a59` | 30,092 |
| Martian Mono | `martian-mono-latin.woff2` | `wght 400..500` | 400 500 | SIL OFL 1.1 | `d0be3a78a854bcea` | 23,556 |
| Cormorant Garamond | `cormorant-garamond-latin.woff2` | `wght 300..500` | 300 500 | SIL OFL 1.1 | `d80df8ff5aecd299` | 37,640 |
| Cormorant Garamond *italic* | `cormorant-garamond-italic-latin.woff2` | `wght 300..500` | 300 500 | SIL OFL 1.1 | `6f2f5c3b1abc3d0b` | 39,260 |
| Anton | `anton-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `d0fa07ff63dd60cb` | 18,612 |
| Barlow 400 | `barlow-400-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `b0a8ad37ac45f5fb` | 22,196 |
| Barlow 500 | `barlow-500-latin.woff2` | none (static) | 500 | SIL OFL 1.1 | `cd759df8ef9efc98` | 22,008 |
| Barlow 600 | `barlow-600-latin.woff2` | none (static) | 600 | SIL OFL 1.1 | `4b52ddd4836b592d` | 22,772 |
| Barlow Condensed 500 | `barlow-condensed-500-latin.woff2` | none (static) | 500 | SIL OFL 1.1 | `460f141ec8f6c9a1` | 21,424 |
| Barlow Condensed 600 | `barlow-condensed-600-latin.woff2` | none (static) | 600 | SIL OFL 1.1 | `215a93c696f44203` | 22,308 |
| Archivo Black | `archivo-black-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `25f33e61cf995abd` | 18,604 |
| Prata | `prata-latin.woff2` | none (static) | 400 | SIL OFL 1.1 | `1b85a8c794709747` | 19,224 |

**1,030,132 bytes raw / 1,373,540 base64** for the whole set: the first seven
families are 294,308 bytes, the seven added for the shop Plantillas 491,776, Cormorant
Garamond, added for the yoga Plantilla `amalia-salvia`, 76,900, Anton, Barlow and Barlow
Condensed, added for the gym Plantilla `forja`, 129,320, and Archivo Black, for its model B
`forja-fucsia`, 18,604, and Prata, for the light model `noir-claro` of the perfume Plantilla,
19,224. No single
file pays all of it: each page embeds only the families it names. Newsreader alone is 278,872
bytes for roman and italic, because both carry the optical-size axis; a Plantilla that
uses it should embed only the styles it actually sets.

### Copyright notices, as required by OFL §1

| Family | Licence file | Copyright |
|---|---|---|
| Fraunces | `fraunces-OFL.txt` | Copyright 2018 The Fraunces Project Authors (https://github.com/undercasetype/Fraunces) |
| Instrument Serif | `instrumentserif-OFL.txt` | Copyright 2022 The Instrument Serif Project Authors (https://github.com/Instrument/instrument-serif) |
| Inter Tight | `intertight-OFL.txt` | Copyright 2022 The Inter Project Authors (https://github.com/rsms/inter-tight) |
| DM Sans | `dmsans-OFL.txt` | Copyright 2014 The DM Sans Project Authors (https://github.com/googlefonts/dm-fonts) |
| Source Sans 3 | `sourcesans3-OFL.txt` | Copyright 2010-2020 Adobe (http://www.adobe.com/), with Reserved Font Name 'Source'. All Rights Reserved. Source is a trademark of Adobe in the United States and/or other countries. |
| Archivo · Archivo Expanded | `archivo-OFL.txt` | Copyright 2020 The Archivo Project Authors (https://github.com/Omnibus-Type/Archivo) |
| Bodoni Moda | `bodonimoda-OFL.txt` | Copyright 2020 The Bodoni Moda Project Authors (https://github.com/indestructible-type/Bodoni) |
| Jost | `jost-OFL.txt` | Copyright 2020 The Jost Project Authors (https://github.com/indestructible-type) |
| Newsreader | `newsreader-OFL.txt` | Copyright 2020 The Newsreader Project Authors (http://github.com/productiontype/Newsreader) |
| Schibsted Grotesk | `schibstedgrotesk-OFL.txt` | Copyright 2023 The Schibsted-Grotesk Project Authors (https://github.com/schibsted/schibsted-grotesk) |
| IBM Plex Mono | `ibmplexmono-OFL.txt` | Copyright © 2017 IBM Corp. with Reserved Font Name "Plex" |
| Instrument Sans | `instrumentsans-OFL.txt` | Copyright 2022 The Instrument Sans Project Authors (https://github.com/Instrument/instrument-sans) |
| Martian Mono | `martianmono-OFL.txt` | Copyright 2021 The Martian Mono Project Authors (https://github.com/evilmartians/mono) |
| Cormorant Garamond | `cormorantgaramond-OFL.txt` | Copyright 2015 the Cormorant Project Authors (github.com/CatharsisFonts/Cormorant) |
| Anton | `anton-OFL.txt` | Copyright 2020 The Anton Project Authors (https://github.com/googlefonts/AntonFont.git) |
| Barlow | `barlow-OFL.txt` | Copyright 2017 The Barlow Project Authors (https://github.com/jpt/barlow) |
| Barlow Condensed | `barlowcondensed-OFL.txt` | Copyright 2017 The Barlow Project Authors (https://github.com/jpt/barlow) |
| Archivo Black | `archivoblack-OFL.txt` | Copyright 2017 The Archivo Black Project Authors (https://github.com/Omnibus-Type/ArchivoBlack) |
| Prata | `prata-OFL.txt` | Copyright 2011 The Prata Project Authors (https://github.com/cyrealtype/Prata) |

Archivo and Archivo Expanded share one notice because they are one family.

### Source URLs

Re-fetchable, and the `vNN` is Google's asset revision — if a re-fetch returns a
different `sha256`, the upstream file moved and this table is the record of what
was actually shipped.

| File | Source |
|---|---|
| `fraunces-latin.woff2` | https://fonts.gstatic.com/s/fraunces/v38/6NU78FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0KxC9TeA.woff2 |
| `instrument-serif-latin.woff2` | https://fonts.gstatic.com/s/instrumentserif/v5/jizBRFtNs2ka5fXjeivQ4LroWlx-6zUTjg.woff2 |
| `inter-tight-latin.woff2` | https://fonts.gstatic.com/s/intertight/v9/NGSwv5HMAFg6IuGlBNMjxLsH8ag.woff2 |
| `dm-sans-latin.woff2` | https://fonts.gstatic.com/s/dmsans/v17/rP2Hp2ywxg089UriCZOIHQ.woff2 |
| `source-sans-3-latin.woff2` | https://fonts.gstatic.com/s/sourcesans3/v19/nwpStKy2OAdR1K-IwhWudF-R3w8aZQ.woff2 |
| `archivo-latin.woff2` | https://fonts.gstatic.com/s/archivo/v25/k3kPo8UDI-1M0wlSV9XAw6lQkqWY8Q82sLydOxI.woff2 |
| `archivo-expanded-latin.woff2` | https://fonts.gstatic.com/s/archivo/v25/k3kPo8UDI-1M0wlSV9XAw6lQkqWY8Q8EsLydOxI.woff2 |
| `bodoni-moda-latin.woff2` | https://fonts.gstatic.com/s/bodonimoda/v28/aFTH7PxzY382XsXX63LUYL6GYFksw-NIrKp-rPr1KOxQ.woff2 |
| `bodoni-moda-italic-latin.woff2` | https://fonts.gstatic.com/s/bodonimoda/v28/aFTB7PxzY382XsXX63LUYJSPUqb0qojSAq1rZLktbNxSXgM.woff2 |
| `jost-latin.woff2` | https://fonts.gstatic.com/s/jost/v20/92zatBhPNqw73oTd4g.woff2 |
| `newsreader-latin.woff2` | https://fonts.gstatic.com/s/newsreader/v26/cY9AfjOCX1hbuyalUrK4397yjA.woff2 |
| `newsreader-italic-latin.woff2` | https://fonts.gstatic.com/s/newsreader/v26/cY9CfjOCX1hbuyalUrK439vCjohC.woff2 |
| `schibsted-grotesk-latin.woff2` | https://fonts.gstatic.com/s/schibstedgrotesk/v7/Jqz55SSPQuCQF3t8uOwiUL-taUTtap9Gayo.woff2 |
| `ibm-plex-mono-400-latin.woff2` | https://fonts.gstatic.com/s/ibmplexmono/v20/-F63fjptAgt5VM-kVkqdyU8n1i8q1w.woff2 |
| `ibm-plex-mono-500-latin.woff2` | https://fonts.gstatic.com/s/ibmplexmono/v20/-F6qfjptAgt5VM-kVkqdyU8n3twJwlBFgg.woff2 |
| `instrument-sans-latin.woff2` | https://fonts.gstatic.com/s/instrumentsans/v4/pxiTypc9vsFDm051Uf6KVwgkfoSxQ0GsQv8ToedPibnr0SZe1Q.woff2 |
| `martian-mono-latin.woff2` | https://fonts.gstatic.com/s/martianmono/v6/2V0PKIcADoYhV6w87xrTKjs4CYElh_VS9YA4TlTnaTq9wQ.woff2 |
| `cormorant-garamond-latin.woff2` | https://fonts.gstatic.com/s/cormorantgaramond/v21/co3bmX5slCNuHLi8bLeY9MK7whWMhyjYqXtK.woff2 |
| `cormorant-garamond-italic-latin.woff2` | https://fonts.gstatic.com/s/cormorantgaramond/v21/co3ZmX5slCNuHLi8bLeY9MK7whWMhyjYrEtImSo.woff2 |
| `anton-latin.woff2` | https://fonts.gstatic.com/s/anton/v27/1Ptgg87LROyAm3Kz-C8.woff2 |
| `barlow-400-latin.woff2` | https://fonts.gstatic.com/s/barlow/v13/7cHpv4kjgoGqM7E_DMs5.woff2 |
| `barlow-500-latin.woff2` | https://fonts.gstatic.com/s/barlow/v13/7cHqv4kjgoGqM7E3_-gs51os.woff2 |
| `barlow-600-latin.woff2` | https://fonts.gstatic.com/s/barlow/v13/7cHqv4kjgoGqM7E30-8s51os.woff2 |
| `barlow-condensed-500-latin.woff2` | https://fonts.gstatic.com/s/barlowcondensed/v13/HTxwL3I-JCGChYJ8VI-L6OO_au7B4-Lwz3bWuQ.woff2 |
| `barlow-condensed-600-latin.woff2` | https://fonts.gstatic.com/s/barlowcondensed/v13/HTxwL3I-JCGChYJ8VI-L6OO_au7B4873z3bWuQ.woff2 |
| `archivo-black-latin.woff2` | https://fonts.gstatic.com/s/archivoblack/v23/HTxqL289NzCGg4MzN6KJ7eW6CYyF_g.woff2 |
| `prata-latin.woff2` | https://fonts.gstatic.com/s/prata/v22/6xKhdSpbNNCT-sWPCm4.woff2 |

Licence texts came from `raw.githubusercontent.com/google/fonts/main/ofl/<dir>/OFL.txt`.

## Why some families are several files

The registry key is the CSS family name a page writes, so one key cannot repeat. Three of the
seven families added for the shop Plantillas need more than one file under that one name:
Bodoni Moda and Newsreader are set in **italic** as well as roman, and IBM Plex Mono is two
**static** weights, 400 and 500. Such an entry carries a `faces` list, each face with its own
`style` and `weight`, and `_fonts.php` emits one `@font-face` per face. Registering only the
roman would make the browser **synthesise** the italic by slanting the upright drawing — a
different letter from the designed one, and as silent as the synthetic bold above.

## How the bytes reach a page

`_fonts.php` holds the registry and emits the `@font-face` block.
`_embed-fonts.php` writes that block into the four static mockups between
`NM-FONTS:BEGIN` / `NM-FONTS:END` markers and is safe to re-run.
`../gallery/_build-gallery.php` calls the same helper when it generates
`index.html`. `framework-audit.php`'s `RT_MOCKUP_FONT_NOT_EMBEDDED` fails any
mockup that names a family it does not embed, so this cannot quietly come undone.
