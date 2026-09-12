<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/helpers.php';
ensure_schema($pdo);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Studio — Dark Pink Edition</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="topbar py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <div class="brand-mark"><i class="bi bi-qr-code"></i></div>
      <div class="brand-text">QR <span>Studio</span></div>
    </div>
    <span class="badge-pink d-none d-sm-inline-flex">
      <i class="bi bi-database-fill me-1"></i>MySQL · MYQRCODEBASE_01
    </span>
  </div>
</nav>

<main class="container py-5">

  <header class="text-center mb-5">
    <h1 class="hero-title mb-3">Generate &amp; Read QR Codes<br>Beautifully</h1>
    <p class="hero-sub">
      Build your own fields, drag to sort them, generate QR codes with smart
      auto-formatted values, and save them to MySQL and <code>/record/</code>.
    </p>
  </header>

  <div class="d-flex justify-content-center mb-4">
    <ul class="nav pill-tabs" id="mainTabs" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-generate" type="button"><i class="bi bi-magic"></i> Generator</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-read" type="button"><i class="bi bi-upload"></i> Reader</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-builder" type="button"><i class="bi bi-sliders"></i> Field Builder</button></li>
    </ul>
  </div>

  <div class="tab-content">

    <!-- ======================== GENERATOR PANE ======================== -->
    <div class="tab-pane fade show active" id="pane-generate" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="glass-card p-4 p-md-5 h-100">
            <div class="section-title mb-4"><i class="bi bi-person-vcard"></i> Dynamic Details</div>
            <form id="qrForm" novalidate>
              <div class="row g-3" id="dynamicFields"></div>
              <div class="d-flex flex-wrap gap-3 mt-4">
                <button type="submit" class="btn btn-pink"><i class="bi bi-magic me-2"></i>Generate QR Code</button>
                <button type="reset" class="btn btn-ghost" id="resetBtn"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset</button>
              </div>
              <div id="formMsg" class="mt-3"></div>
            </form>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="glass-card p-4 p-md-5 h-100 text-center">
            <div class="section-title mb-4 justify-content-center"><i class="bi bi-qr-code"></i> Live Preview</div>
            <div id="qrHolder" class="qr-holder empty">
              <div class="qr-empty" id="qrEmpty">
                <i class="bi bi-qr-code"></i>
                <div class="fw-semibold mb-1">No QR code yet</div>
                <small>Fill the form and click<br>"Generate QR Code".</small>
              </div>
            </div>
            <div class="d-grid gap-2 mt-4">
              <button type="button" class="btn btn-pink" id="downloadBtn" disabled>
                <i class="bi bi-download me-2"></i>Download PNG
              </button>
              <button type="button" class="btn btn-ghost" id="saveBtn" disabled>
                <i class="bi bi-cloud-arrow-up me-2"></i>Save to MySQL &amp; /record
              </button>
            </div>
            <div id="saveMsg" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================== READER PANE ========================== -->
    <div class="tab-pane fade" id="pane-read" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="glass-card p-4 p-md-5 h-100">
            <div class="section-title mb-4"><i class="bi bi-image"></i> Upload QR Image</div>
            <div class="drop-zone" id="dropZone">
              <i class="bi bi-cloud-arrow-up"></i>
              <div class="dz-title">Drop an image here</div>
              <div class="dz-hint">or click to browse · PNG / JPG / WEBP / GIF</div>
            </div>
            <input type="file" id="qrFile" accept="image/*" hidden>
            <div class="d-flex flex-wrap gap-3 mt-4">
              <button type="button" class="btn btn-ghost" id="clearReaderBtn">
                <i class="bi bi-trash3 me-2"></i>Clear
              </button>
            </div>
            <canvas id="readerCanvas" hidden></canvas>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="glass-card p-4 p-md-5 h-100">
            <div class="section-title mb-4"><i class="bi bi-clipboard-check"></i> Decoded Result</div>
            <div id="readResult">
              <p style="color:var(--muted);" class="mb-0">
                <i class="bi bi-info-circle me-2"></i>Upload a QR image to see the decoded content here.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================= FIELD BUILDER ========================= -->
    <div class="tab-pane fade" id="pane-builder" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="glass-card p-4 p-md-5 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div class="section-title"><i class="bi bi-list-check"></i> Existing Fields</div>
              <button class="btn btn-ghost btn-sm px-3 py-2" id="newFieldBtn">
                <i class="bi bi-plus-lg me-2"></i>New Field
              </button>
            </div>
            <p class="mb-3" style="color:var(--muted);font-size:.85rem;">
              <i class="bi bi-grip-vertical me-1"></i>
              Drag the <i class="bi bi-grip-vertical"></i> handle to re-order fields. Order is saved automatically.
            </p>
            <div id="fieldList" class="field-sortable"></div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="glass-card p-4 p-md-5 h-100">
            <div class="section-title mb-4">
              <i class="bi bi-pencil-square"></i> <span id="editorTitle">Add Field</span>
            </div>

            <form id="fieldForm">
              <input type="hidden" id="field_id">

              <div class="mb-3">
                <label class="form-label" for="field_label">Label</label>
                <input type="text" class="form-control" id="field_label" required maxlength="150">
              </div>

              <div class="mb-3">
                <label class="form-label" for="field_key">Field Key</label>
                <input type="text" class="form-control" id="field_key" maxlength="64" placeholder="auto from label">
              </div>

              <div class="mb-3">
                <label class="form-label" for="field_type">Type</label>
                <select class="form-select" id="field_type">
                  <option value="text">Text</option>
                  <option value="textarea">Textarea</option>
                  <option value="number">Number</option>
                  <option value="email">Email</option>
                  <option value="tel">Telephone</option>
                  <option value="date">Date</option>
                  <option value="select">Select (options)</option>
                  <option value="locked">Locked / Auto-format</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label" for="field_placeholder">Placeholder</label>
                <input type="text" class="form-control" id="field_placeholder" maxlength="200">
              </div>

              <div class="mb-3 d-none" id="optsWrap">
                <label class="form-label" for="field_options">Options (one per line or comma-separated)</label>
                <textarea class="form-control" id="field_options" rows="4" placeholder="Male&#10;Female&#10;Other"></textarea>
              </div>

              <div class="mb-3 d-none" id="lockWrap">
                <label class="form-label" for="field_autoformat">Auto-Format</label>
                <input type="text" class="form-control" id="field_autoformat"
                       placeholder="EMP-{YYYY}-{#####}" autocomplete="off">

                <div class="token-palette">
                  <div class="palette-section">
                    <div class="palette-title">
                      <i class="bi bi-calendar3"></i> Date / Time (current)
                    </div>
                    <div class="palette-chips">
                      <button type="button" class="token-chip" data-token="{YYYY}">{YYYY}</button>
                      <button type="button" class="token-chip" data-token="{YY}">{YY}</button>
                      <button type="button" class="token-chip" data-token="{MM}">{MM}</button>
                      <button type="button" class="token-chip" data-token="{DD}">{DD}</button>
                      <button type="button" class="token-chip" data-token="{HH}">{HH}</button>
                      <button type="button" class="token-chip" data-token="{MI}">{MI}</button>
                      <button type="button" class="token-chip" data-token="{SS}">{SS}</button>
                    </div>
                  </div>

                  <div class="palette-section">
                    <div class="palette-title">
                      <i class="bi bi-123"></i> Counter (from record ID)
                    </div>
                    <div class="palette-chips">
                      <button type="button" class="token-chip" data-token="{#}">{#}</button>
                      <button type="button" class="token-chip" data-token="{###}">{###}</button>
                      <button type="button" class="token-chip" data-token="{#####}">{#####}</button>
                      <button type="button" class="token-chip" data-token="{########}">{########}</button>
                    </div>
                  </div>

                  <div class="palette-section">
                    <div class="palette-title">
                      <i class="bi bi-link-45deg"></i> Reference another field
                    </div>
                    <div id="fieldRefPalette" class="palette-fields">
                      <p class="mb-0" style="color:var(--muted);font-size:.8rem;">
                        No other fields yet.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="form-text mt-2" style="color:var(--muted);font-size:.78rem;">
                  Click any chip above to insert it at the cursor position.
                </div>
              </div>

              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label" for="field_sort">Sort order</label>
                  <input type="number" class="form-control" id="field_sort" value="0" min="0">
                </div>
                <div class="col-6 d-flex align-items-end">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="field_required" checked>
                    <label class="form-check-label" for="field_required">Required</label>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-3 mt-4">
                <button type="submit" class="btn btn-pink"><i class="bi bi-save me-2"></i>Save Field</button>
                <button type="button" class="btn btn-ghost" id="cancelFieldBtn"><i class="bi bi-x-lg me-2"></i>Cancel</button>
              </div>

              <div id="fieldMsg" class="mt-3"></div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ============================ RECORDS ============================ -->
  <div class="divider"></div>

  <section>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div class="section-title"><i class="bi bi-clock-history"></i> Recent Saved Records</div>
      <button class="btn btn-ghost btn-sm px-3 py-2" id="refreshRecords">
        <i class="bi bi-arrow-repeat me-2"></i>Refresh
      </button>
    </div>
    <div class="row g-3" id="recordsGrid"></div>
  </section>

</main>

<footer class="footer">
  <div class="container text-center">
    Built with <i class="bi bi-heart-fill heart"></i> using HTML, CSS, JS, Bootstrap, PHP &amp; MySQL
    · <span class="text-pink">QR Studio</span>
  </div>
</footer>

<!-- ============================ VIEW MODAL ============================ -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="bi bi-eye me-2 text-pink"></i>Record Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewModalBody">
        <div class="text-center py-5">
          <div class="spinner-border spinner-pink" role="status" style="width:2rem;height:2rem;"></div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>