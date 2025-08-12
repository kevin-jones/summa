<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consolidate Playwright Summaries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #0b0c10; color: #e6e6e6; }
    .brand { letter-spacing: .5px; }
    textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    #inputText { min-height: 50vh; }
    #outputText { min-height: 30vh; }
    .small-muted { color: #9aa0a6; font-size: .9rem; }
    .token { font-family: inherit; background: #111318; border: 1px solid #2a2d39; border-radius: .5rem; padding: .25rem .5rem; }
    .card { background: #111318; border: 1px solid #2a2d39; }
    .form-select, .form-control { background-color: #0f1117; color: #e6e6e6; border-color: #2a2d39; }
    .form-control:focus, .form-select:focus { background-color: #0f1117; color: #fff; border-color: #646cff; box-shadow: 0 0 0 .2rem rgba(100,108,255,.2); }
    .btn-primary { background: #646cff; border-color: #646cff; }
    .btn-outline-secondary { border-color: #2a2d39; color: #e6e6e6; }
    .btn-outline-secondary:hover { background: #1a1d29; }
    a { color: #8ab4f8; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex align-items-center mb-3">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" class="me-2">
        <path d="M4 4h16v4H4zM4 10h10v4H4zM4 16h16v4H4z" fill="#646cff"/>
      </svg>
      <h1 class="h3 brand mb-0">Consolidate Playwright Summaries</h1>
      <span class="ms-2 small-muted">paste JSON, logs or message snippets, then build a clean summary.json</span>
    </div>

    <div class="row g-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <label for="inputText" class="form-label fw-semibold">1. Paste your inputs</label>
            <textarea id="inputText" class="form-control" placeholder="Paste any mix of Playwright summary.json objects, arrays, or free text with test paths like \n_playwright/additional-merchant.test.ts:10:5\nplaywright/foo.test.ts:20:3, playwright/bar.test.ts:2320:1\n\nMultiple JSON objects in one blob are fine."></textarea>
            <div class="d-flex flex-wrap gap-3 mt-3 align-items-center">
              <div class="d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="stripCodeFences" checked>
                <label class="form-check-label small" for="stripCodeFences">Strip markdown fences</label>
              </div>
              <div class="d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="normalisePaths" checked>
                <label class="form-check-label small" for="normalisePaths">Normalise paths</label>
              </div>
              <div class="d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="uniqueOnly" checked>
                <label class="form-check-label small" for="uniqueOnly">De‑duplicate</label>
              </div>
              <div class="ms-auto small-muted">Detected items: <span class="token" id="detectedCount">0</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-8">
        <div class="card h-100">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="assumeAs">2. Assume unidentified items are</label>
                <select id="assumeAs" class="form-select">
                  <option value="failed" selected>Failed</option>
                  <option value="timedOut">Timed Out</option>
                  <option value="flakey">Flakey</option>
                  <option value="interrupted">Interrupted</option>
                  <option value="warned">Warned</option>
                  <option value="skipped">Skipped</option>
                  <option value="passed">Passed</option>
                </select>
                <div class="small-muted mt-2">Anything that is not inside a parsed JSON summary will be added to this bucket.</div>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="outputBucket">3. Output bucket</label>
                <select id="outputBucket" class="form-select">
                  <option value="__allFailures" selected>All Failures (failed, timedOut, flakey, interrupted)</option>
                  <option value="failed">Failed</option>
                  <option value="timedOut">Timed Out</option>
                  <option value="flakey">Flakey</option>
                  <option value="interrupted">Interrupted</option>
                  <option value="warned">Warned</option>
                  <option value="skipped">Skipped</option>
                  <option value="passed">Passed</option>
                  <option value="__all">All Buckets</option>
                </select>
                <div class="small-muted mt-2">Choose what to keep in the generated summary.json output.</div>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
              <button id="goBtn" class="btn btn-primary">GO</button>
              <button id="copyBtn" class="btn btn-outline-secondary">Copy output</button>
              <button id="downloadBtn" class="btn btn-outline-secondary">Download summary.json</button>
              <span class="ms-auto small-muted">Output items: <span class="token" id="outputCount">0</span></span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="fw-semibold mb-2">Buckets overview</div>
            <div id="bucketStats" class="small"></div>
            <div class="small-muted mt-2">These counts reflect everything parsed from the input before filtering for the output bucket.</div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <label for="outputText" class="form-label fw-semibold">4. Output summary.json</label>
            <textarea id="outputText" class="form-control" placeholder="Click GO to generate..."></textarea>
          </div>
        </div>
      </div>
    </div>

    <footer class="mt-4 small-muted">
      Tip: you can paste several summary.json blobs, Slack messages, and ad‑hoc lists all at once; the tool will merge and de‑duplicate.
    </footer>
  </div>

  <script>
  // --- Utility helpers ---
  const BUCKETS = ["passed","skipped","failed","warned","interrupted","timedOut","flakey"]; // canonical keys
  const FAILURE_SET = new Set(["failed","timedOut","flakey","interrupted"]);

  function normalisePath(p) {
    // Trim quotes and commas, strip backticks, backslashes to forward slashes, remove leading underscores when common
    let s = String(p).trim();
    s = s.replace(/^['"`\s]+|['"`,\s]+$/g, "");
    s = s.replace(/\\/g, "/");
    // Common prefixes like _playwright/ vs playwright/
    s = s.replace(/^_?(playwright\//, "playwright/");
    return s;
  }

  function looksLikeTestRef(line) {
    const s = line.trim();
    if (!s) return false;
    // e.g., path/abc.test.ts:10:5 or :10 or :10:5, tolerate commas
    const re = /[\w@$.\-/\\]+\.test\.[tj]sx?(?::\d+(?::\d+)?)?/i;
    return re.test(s);
  }

  function extractTestRefsFromText(text) {
    const refs = [];
    // Split on newlines and commas
    const parts = text.split(/\n|,/);
    for (let raw of parts) {
      if (looksLikeTestRef(raw)) {
        refs.push(raw.trim());
      }
    }
    return refs;
  }

  function stripCodeFences(text) {
    // Remove triple backtick blocks wrappers, keep inner content
    return text.replace(/```[\s\S]*?```/g, m => m.replace(/^```[a-zA-Z]*\n?/, "").replace(/```$/, ""));
  }

  function tryParseJSONBlocks(text) {
    const objs = [];
    // Find candidate JSON objects by braces balance scanning
    // Also pick up standalone arrays with keys we know
    const candidates = [];

    // Quick hits: fenced JSON code blocks
    const fenceRE = /```(?:json)?\n([\s\S]*?)\n```/gi;
    let m;
    while ((m = fenceRE.exec(text)) !== null) candidates.push(m[1]);

    // Heuristic: split on lines that look like start of an object
    const pieces = text.split(/\n{2,}/);
    for (const piece of pieces) {
      const trimmed = piece.trim();
      if (trimmed.startsWith("{") && trimmed.includes(":") && trimmed.endsWith("}")) {
        candidates.push(trimmed);
      }
    }

    // As a last resort, try to extract balanced braces segments
    const idxs = [];
    const stack = [];
    for (let i=0;i<text.length;i++) {
      const ch = text[i];
      if (ch === '{') stack.push(i);
      else if (ch === '}') {
        const start = stack.pop();
        if (start !== undefined) {
          const seg = text.slice(start, i+1);
          if (seg.includes('"failed"') || seg.includes('"passed"') || seg.includes('"skipped"')) {
            candidates.push(seg);
          }
        }
      }
    }

    for (const c of candidates) {
      try {
        const obj = JSON.parse(c);
        if (typeof obj === 'object' && obj) objs.push(obj);
      } catch {}
    }
    return objs;
  }

  function mergeSummaries(objs) {
    const merged = {
      durationInMS: 0,
      passed: [], skipped: [], failed: [], warned: [], interrupted: [], timedOut: [], flakey: [],
      status: "passed",
      startedAt: Date.now()
    };
    const pushUnique = (arr, v) => { if (!arr.includes(v)) arr.push(v); };

    for (const o of objs) {
      if (typeof o.durationInMS === 'number') merged.durationInMS += o.durationInMS;
      for (const k of BUCKETS) {
        if (Array.isArray(o[k])) {
          for (let item of o[k]) pushUnique(merged[k], String(item));
        }
      }
    }

    merged.status = merged.failed.length || merged.timedOut.length || merged.flakey.length || merged.interrupted.length ? "failed" : "passed";
    return merged;
  }

  function classifyFreeText(text, bucket, normalise) {
    const refs = extractTestRefsFromText(text);
    return refs.map(r => normalise ? normalisePath(r) : r);
  }

  function buildOutput(allBuckets, outputBucket) {
    const base = { durationInMS: 0, status: "passed", startedAt: Date.now() };
    const out = { ...base, passed: [], skipped: [], failed: [], warned: [], interrupted: [], timedOut: [], flakey: [] };

    const includeAll = outputBucket === "__all";
    const includeFailures = outputBucket === "__allFailures";

    const add = (k, arr) => { out[k] = [...arr]; };

    for (const k of BUCKETS) {
      if (includeAll) add(k, allBuckets[k]);
      else if (includeFailures && FAILURE_SET.has(k)) add(k, allBuckets[k]);
      else if (k === outputBucket) add(k, allBuckets[k]);
      else add(k, []);
    }

    out.status = out.failed.length || out.timedOut.length || out.flakey.length || out.interrupted.length ? "failed" : "passed";
    return out;
  }

  function updateBucketStats(buckets) {
    const el = document.getElementById('bucketStats');
    const items = BUCKETS.map(k => `<div class=\"d-flex justify-content-between\"><span class=\"text-capitalize\">${k}</span><span class=\"token\">${buckets[k].length}</span></div>`).join("");
    el.innerHTML = items || '<em>No items yet.</em>';
  }

  // --- Main wiring ---
  const inputEl = document.getElementById('inputText');
  const outputEl = document.getElementById('outputText');
  const detectedCountEl = document.getElementById('detectedCount');
  const outputCountEl = document.getElementById('outputCount');

  function process() {
    const normalise = document.getElementById('normalisePaths').checked;
    const dedupe = document.getElementById('uniqueOnly').checked;
    const assumeAs = document.getElementById('assumeAs').value;
    const outputBucket = document.getElementById('outputBucket').value;

    let text = inputEl.value || "";
    if (document.getElementById('stripCodeFences').checked) text = stripCodeFences(text);

    // Parse JSON summaries
    const objs = tryParseJSONBlocks(text);

    // Merge JSON buckets
    const merged = mergeSummaries(objs);

    // Remove JSON segments from free text to reduce duplicates
    let cleanedText = text;
    for (const o of objs) {
      for (const k of BUCKETS) {
        if (Array.isArray(o[k])) {
          for (const item of o[k]) {
            const needle = String(item);
            cleanedText = cleanedText.replaceAll(needle, "");
          }
        }
      }
    }

    // Extract free text refs
    let freeRefs = classifyFreeText(cleanedText, assumeAs, normalise);

    // Normalise all merged buckets if required
    for (const k of BUCKETS) {
      merged[k] = merged[k].map(v => normalise ? normalisePath(v) : String(v));
    }

    // Add free text items into the assumed bucket
    merged[assumeAs].push(...freeRefs);

    // De‑duplicate across buckets individually
    if (dedupe) {
      for (const k of BUCKETS) {
        const seen = new Set();
        merged[k] = merged[k].filter(v => (seen.has(v) ? false : (seen.add(v), true)));
      }
    }

    // Counts and stats
    const totalDetected = BUCKETS.reduce((a,k)=>a+merged[k].length, 0);
    detectedCountEl.textContent = String(totalDetected);
    updateBucketStats(merged);

    // Build output according to the chosen bucket
    const outObj = buildOutput(merged, outputBucket);
    outputEl.value = JSON.stringify(outObj, null, 2);

    // Output count equals sum of arrays present in outObj (excluding passed/skipped etc if empty)
    const outCount = BUCKETS.reduce((a,k)=>a+(outObj[k]?.length||0), 0);
    outputCountEl.textContent = String(outCount);
  }

  document.getElementById('goBtn').addEventListener('click', process);

  document.getElementById('copyBtn').addEventListener('click', async () => {
    if (!outputEl.value) return;
    try { await navigator.clipboard.writeText(outputEl.value); }
    catch {}
  });

  document.getElementById('downloadBtn').addEventListener('click', () => {
    const blob = new Blob([outputEl.value || '' ], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'summary.json';
    document.body.appendChild(a); a.click();
    setTimeout(()=>{ URL.revokeObjectURL(url); a.remove(); }, 0);
  });

  // Live detected counter while typing
  let debounce;
  inputEl.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(()=>{
      const text = document.getElementById('stripCodeFences').checked ? stripCodeFences(inputEl.value) : inputEl.value;
      const objs = tryParseJSONBlocks(text);
      const merged = mergeSummaries(objs);
      const cleaned = objs.length ? objs.reduce((acc, o) => {
        let tmp = acc;
        for (const k of BUCKETS) if (Array.isArray(o[k])) for (const it of o[k]) tmp = tmp.replaceAll(String(it), "");
        return tmp;
      }, text) : text;
      const freeRefs = extractTestRefsFromText(cleaned);
      const total = BUCKETS.reduce((a,k)=>a+merged[k].length, 0) + freeRefs.length;
      detectedCountEl.textContent = String(total);
      updateBucketStats(merged);
    }, 250);
  });
  </script>
</body>
</html>

