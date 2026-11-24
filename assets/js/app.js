document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.querySelector('.side');
  const sidebarToggle = document.querySelector('.mobile-toggle') || document.querySelector('.topbar-toggle');
  const sidebarClose = document.querySelector('.side-close') || document.querySelector('.sidebar-close');
  const sidebarOverlay = document.querySelector('.side-overlay') || document.querySelector('.sidebar-overlay');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (sidebarOverlay) sidebarOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (sidebarOverlay) sidebarOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

  if (sidebar) {
    sidebar.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 820) {
          closeSidebar();
        }
      });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 820 && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  const linksList = document.getElementById('linksList');
  if (linksList) {
    let draggedItem = null;
    let placeholder = null;
    let initialIndex = -1;
    let isDragging = false;
    let startY = 0;
    let startX = 0;
    let dragThresholdPassed = false;
    const csrfToken = document.querySelector('input[name="csrf"]')?.value || '';
    const reorderUrl = linksList.dataset.reorderUrl || window.location.href;

    function updateMoveButtons() {
      const items = linksList.querySelectorAll('.lk-item');
      items.forEach(function (item, index) {
        const upBtn = item.querySelector('button[value="move_up"]');
        const downBtn = item.querySelector('button[value="move_down"]');
        if (upBtn) {
          if (index === 0) {
            upBtn.setAttribute('disabled', 'disabled');
            upBtn.style.opacity = '0.3';
            upBtn.style.cursor = 'default';
          } else {
            upBtn.removeAttribute('disabled');
            upBtn.style.opacity = '';
            upBtn.style.cursor = '';
          }
        }
        if (downBtn) {
          if (index === items.length - 1) {
            downBtn.setAttribute('disabled', 'disabled');
            downBtn.style.opacity = '0.3';
            downBtn.style.cursor = 'default';
          } else {
            downBtn.removeAttribute('disabled');
            downBtn.style.opacity = '';
            downBtn.style.cursor = '';
          }
        }
      });
    }

    function showToastMessage(msg, isError) {
      let t = document.querySelector('.toast');
      if (!t) {
        t = document.createElement('div');
        t.className = 'toast';
        document.body.appendChild(t);
      }
      t.className = 'toast ' + (isError ? 'err' : 'ok');
      t.textContent = msg;
      t.style.animation = 'none';
      t.offsetHeight;
      t.style.animation = 'toast 3.5s ease forwards';
    }

    function saveOrder() {
      const items = Array.from(linksList.querySelectorAll('.lk-item'));
      const order = items.map(function (el) { return el.dataset.id; }).filter(Boolean);
      const formData = new FormData();
      formData.append('action', 'reorder_links');
      formData.append('csrf', csrfToken);
      order.forEach(function (id) { formData.append('order[]', id); });

      fetch(reorderUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.ok) {
          updateMoveButtons();
          showToastMessage('Link order saved.', false);
        } else {
          showToastMessage('Failed to save order.', true);
        }
      })
      .catch(function () {
        showToastMessage('Failed to save order.', true);
      });
    }

    function getDragAfterElement(container, y) {
      const draggableElements = Array.from(container.querySelectorAll('.lk-item:not(.is-dragging)'));
      return draggableElements.reduce(function (closest, child) {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset: offset, element: child };
        } else {
          return closest;
        }
      }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    linksList.addEventListener('pointerdown', function (e) {
      const handle = e.target.closest('.drag-handle');
      if (!handle) return;
      draggedItem = handle.closest('.lk-item');
      if (!draggedItem) return;

      startX = e.clientX;
      startY = e.clientY;
      dragThresholdPassed = false;
      isDragging = false;
      const items = Array.from(linksList.querySelectorAll('.lk-item'));
      initialIndex = items.indexOf(draggedItem);

      handle.setPointerCapture(e.pointerId);

      function onPointerMove(moveEvent) {
        if (!draggedItem) return;
        const deltaY = moveEvent.clientY - startY;
        const deltaX = moveEvent.clientX - startX;

        if (!dragThresholdPassed) {
          if (Math.abs(deltaY) > 4 || Math.abs(deltaX) > 4) {
            dragThresholdPassed = true;
            isDragging = true;
            draggedItem.classList.add('is-dragging');

            placeholder = document.createElement('div');
            placeholder.className = 'lk-placeholder';
            placeholder.style.height = draggedItem.offsetHeight + 'px';
            linksList.insertBefore(placeholder, draggedItem);
          } else {
            return;
          }
        }

        if (moveEvent.cancelable) moveEvent.preventDefault();

        const afterElement = getDragAfterElement(linksList, moveEvent.clientY);
        if (afterElement == null) {
          linksList.appendChild(placeholder);
        } else {
          linksList.insertBefore(placeholder, afterElement);
        }
      }

      function onPointerUp(upEvent) {
        handle.removeEventListener('pointermove', onPointerMove);
        handle.removeEventListener('pointerup', onPointerUp);
        handle.removeEventListener('pointercancel', onPointerUp);

        try {
          if (handle.hasPointerCapture(upEvent.pointerId)) {
            handle.releasePointerCapture(upEvent.pointerId);
          }
        } catch (_) {}

        if (isDragging && draggedItem && placeholder) {
          placeholder.replaceWith(draggedItem);
          draggedItem.classList.remove('is-dragging');
          if (placeholder.parentNode) placeholder.remove();

          const currentItems = Array.from(linksList.querySelectorAll('.lk-item'));
          const newIndex = currentItems.indexOf(draggedItem);
          if (newIndex !== initialIndex) {
            saveOrder();
          }
        }

        draggedItem = null;
        placeholder = null;
        isDragging = false;
        dragThresholdPassed = false;
      }

      handle.addEventListener('pointermove', onPointerMove);
      handle.addEventListener('pointerup', onPointerUp);
      handle.addEventListener('pointercancel', onPointerUp);
    });
  }

  document.querySelectorAll('[data-drop]').forEach(function (d) {
    const inp = d.querySelector('input');
    const img = d.querySelector('img');
    const ico = d.querySelector('svg');
    const title = d.querySelector('b');
    const sub = d.querySelector('.sub');
    const hint = d.querySelector('.hint');
    const clr = d.querySelector('.drop-clear');
    const has = !!d.dataset.src;
    const hintTxt = hint ? hint.textContent : '';
    const t0 = has ? 'Change photo' : 'Upload a photo';
    const s0 = has ? 'Drag & drop a new one, or click to browse' : (sub ? sub.textContent : '');
    let url = null;

    if (title) title.textContent = t0;
    if (sub) sub.textContent = s0;

    function show(src) {
      if (src) {
        if (img) {
          img.src = src;
          img.hidden = false;
        }
        if (ico) ico.style.display = 'none';
      } else {
        if (img) {
          img.hidden = true;
          img.removeAttribute('src');
        }
        if (ico) ico.style.display = '';
      }
    }

    function bad(m) {
      if (hint) {
        hint.textContent = m;
        hint.classList.add('bad');
      }
      if (inp) inp.value = '';
    }

    function reset() {
      if (inp) inp.value = '';
      if (url) URL.revokeObjectURL(url);
      url = null;
      show(d.dataset.src || '');
      if (title) title.textContent = t0;
      if (sub) sub.textContent = s0;
      if (hint) {
        hint.textContent = hintTxt;
        hint.classList.remove('bad');
      }
      if (clr) clr.hidden = true;
      d.classList.remove('has');
    }

    function pick(f) {
      if (!f) return;
      if (!/^image\/(png|jpe?g|webp|gif)$/.test(f.type)) {
        return bad('Please choose a JPG, PNG, WEBP or GIF image.');
      }
      if (f.size > 3 * 1024 * 1024) {
        return bad('That image is over 3MB — try a smaller one.');
      }
      if (url) URL.revokeObjectURL(url);
      url = URL.createObjectURL(f);
      show(url);
      if (title) title.textContent = f.name;
      if (sub) {
        const sizeStr = f.size < 1048576 ? Math.round(f.size / 1024) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
        sub.textContent = sizeStr + ' · ready to save';
      }
      if (hint) {
        hint.textContent = 'Click to choose a different photo';
        hint.classList.remove('bad');
      }
      if (clr) clr.hidden = false;
      d.classList.add('has');
    }

    if (has) show(d.dataset.src);
    if (inp) inp.addEventListener('change', function () { pick(inp.files[0]); });
    if (clr) clr.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      reset();
    });

    ['dragenter', 'dragover'].forEach(function (ev) {
      d.addEventListener(ev, function (e) {
        e.preventDefault();
        d.classList.add('over');
      });
    });

    ['dragleave', 'dragend'].forEach(function (ev) {
      d.addEventListener(ev, function () {
        d.classList.remove('over');
      });
    });

    d.addEventListener('drop', function (e) {
      e.preventDefault();
      d.classList.remove('over');
      const f = e.dataTransfer.files[0];
      if (!f) return;
      try {
        const dt = new DataTransfer();
        dt.items.add(f);
        if (inp) inp.files = dt.files;
      } catch (_) {}
      pick(f);
    });
  });

  const rowsEl = document.getElementById('addRows');
  if (rowsEl) {
    function addRow(name, url) {
      const r = document.createElement('div');
      r.className = 'arow';
      r.innerHTML =
        '<input name="names[]" placeholder="Title (e.g. Instagram)" autocomplete="off">' +
        '<input name="urls[]" dir="ltr" placeholder="https://instagram.com/yourname" autocomplete="off" inputmode="url">' +
        '<button type="button" class="ib rm" aria-label="Remove link"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>';

      const inputs = r.querySelectorAll('input');
      const n = inputs[0];
      const u = inputs[1];
      n.value = name || '';
      u.value = url || '';

      r.querySelector('.rm').onclick = function () {
        if (rowsEl.children.length === 1) {
          n.value = '';
          u.value = '';
          n.focus();
          return;
        }
        r.remove();
      };
      rowsEl.appendChild(r);
      return r;
    }

    const addRowBtn = document.getElementById('addRow');
    if (addRowBtn) {
      addRow();
      addRowBtn.onclick = function () {
        addRow().querySelector('input').focus();
      };
    }

    document.querySelectorAll('#presets .chip').forEach(function (c) {
      c.onclick = function () {
        let r = Array.from(rowsEl.children).find(function (x) {
          const i = x.querySelectorAll('input');
          return !i[0].value.trim() && !i[1].value.trim();
        });
        if (!r) r = addRow();
        const inputs = r.querySelectorAll('input');
        inputs[0].value = c.dataset.name;
        inputs[1].value = c.dataset.url;
        inputs[1].focus();
        inputs[1].setSelectionRange(inputs[1].value.length, inputs[1].value.length);
      };
    });
  }

  const globalSearchInput = document.getElementById('globalSearchInput');
  const globalSearchResults = document.getElementById('globalSearchResults');
  if (globalSearchInput && globalSearchResults) {
    let debounceTimer = null;
    globalSearchInput.addEventListener('input', function () {
      const q = this.value.trim();
      clearTimeout(debounceTimer);
      if (q.length < 2) {
        globalSearchResults.innerHTML = '';
        globalSearchResults.style.display = 'none';
        return;
      }
      debounceTimer = setTimeout(function () {
        const searchUrl = globalSearchInput.dataset.searchUrl || '/test/admin/pages/search.php';
        fetch(searchUrl + '?q=' + encodeURIComponent(q))
          .then(function (res) { return res.json(); })
          .then(function (data) {
            let html = '';
            if (data && data.users && data.users.length) {
              html += '<div class="search-group-title">Members</div>';
              data.users.forEach(function (u) {
                const href = u.profile_url || ('/' + encodeURIComponent(u.username));
                html += '<a class="search-result-item" href="' + href + '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><div><strong>' + escapeHtml(u.name) + '</strong> <small style="color:var(--mut)">@' + escapeHtml(u.username) + '</small></div></a>';
              });
            }
            if (data && data.links && data.links.length) {
              html += '<div class="search-group-title">Destinations</div>';
              data.links.forEach(function (l) {
                html += '<a class="search-result-item" href="' + escapeHtml(l.url) + '" target="_blank" rel="noopener"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg><div><strong>' + escapeHtml(l.name) + '</strong> <small style="color:var(--mut)">' + l.clicks + ' clicks</small></div></a>';
              });
            }
            if (!html) {
              html = '<div class="search-empty">No results found for "' + escapeHtml(q) + '"</div>';
            }
            globalSearchResults.innerHTML = html;
            globalSearchResults.style.display = 'block';
          })
          .catch(function () {
            globalSearchResults.style.display = 'none';
          });
      }, 200);
    });

    document.addEventListener('click', function (e) {
      if (!globalSearchInput.contains(e.target) && !globalSearchResults.contains(e.target)) {
        globalSearchResults.style.display = 'none';
      }
    });

    globalSearchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        globalSearchResults.style.display = 'none';
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  window.openEditModal = function (feat) {
    const modal = document.getElementById('editFeatureModal');
    if (!modal) return;
    document.getElementById('modalFeatureId').value = feat ? (feat.id || '') : '';
    document.getElementById('modalAction').value = feat ? 'update_feature' : 'create_feature';
    document.getElementById('modalTitleText').textContent = feat ? 'Edit Feature Recommendation' : 'Add Feature Recommendation';
    document.getElementById('modalTitle').value = feat ? (feat.title || '') : '';
    document.getElementById('modalCategory').value = feat ? (feat.category || 'User Experience') : 'User Experience';
    document.getElementById('modalPriority').value = feat ? (feat.priority || 'Medium') : 'Medium';
    document.getElementById('modalComplexity').value = feat ? (feat.complexity || 'Medium') : 'Medium';
    document.getElementById('modalStatus').value = feat ? (feat.status || 'suggested') : 'suggested';
    document.getElementById('modalAudience').value = feat ? (feat.target_audience || '') : '';
    document.getElementById('modalDescription').value = feat ? (feat.description || '') : '';
    document.getElementById('modalBenefit').value = feat ? (feat.benefit || '') : '';
    document.getElementById('modalDbChanges').value = feat ? (feat.db_changes || '') : '';
    document.getElementById('modalDependencies').value = feat ? (feat.dependencies || '') : '';
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeEditModal = function () {
    const modal = document.getElementById('editFeatureModal');
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      window.closeEditModal();
    }
  });

  document.addEventListener('click', function (e) {
    const copyBtn = e.target.closest('.copy-btn');
    if (copyBtn) {
      e.preventDefault();
      const url = copyBtn.getAttribute('data-url') || window.location.href;
      const textSpan = copyBtn.querySelector('.btn-text');
      const origText = textSpan ? textSpan.textContent : '';

      function onCopied() {
        if (textSpan) textSpan.textContent = 'Copied!';
        setTimeout(function () {
          if (textSpan) textSpan.textContent = origText;
        }, 2000);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(onCopied).catch(function () {
          fallbackCopy(url, onCopied);
        });
      } else {
        fallbackCopy(url, onCopied);
      }
    }

    const shareBtn = e.target.closest('.share-btn');
    if (shareBtn) {
      e.preventDefault();
      const url = shareBtn.getAttribute('data-url') || window.location.href;
      const title = shareBtn.getAttribute('data-title') || document.title;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
      } else {
        const textSpan = shareBtn.querySelector('.btn-text');
        const origText = textSpan ? textSpan.textContent : '';
        function onSharedFallback() {
          if (textSpan) textSpan.textContent = 'Link Copied!';
          setTimeout(function () {
            if (textSpan) textSpan.textContent = origText;
          }, 2000);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(url).then(onSharedFallback).catch(function () {
            fallbackCopy(url, onSharedFallback);
          });
        } else {
          fallbackCopy(url, onSharedFallback);
        }
      }
    }
  });

  function fallbackCopy(text, callback) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      if (callback) callback();
    } catch (err) {}
    document.body.removeChild(ta);
  }
});
