/**
 * DbM DataTables PHP, file: delete-record.js
 * 
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

document.addEventListener("DOMContentLoaded", () => {
  const deleteModal = document.getElementById("deleteModal");
  const deleteButton = document.getElementById("recordDelete");
  const modalDeleteUrl = deleteModal?.dataset.deleteUrl || "#error";
  let selectedId = null;

  // Kliknięcie w dropdown -> otwarcie modala
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest(".deleteRecord");
    if (!btn) return;

    selectedId = btn.dataset.id;
    deleteButton.dataset.id = selectedId;

    new bootstrap.Modal(deleteModal).show();
  });

  // Kliknięcie "Usuń" w modalu -> wywołanie API
  deleteButton.addEventListener("click", async () => {
    const recordId = deleteButton.dataset.id;
    if (!recordId) {
      console.error("No record ID to delete.");
      return;
    }

    try {
      // 1. REST: DELETE /articles/{id}
      let response = await fetch(`${modalDeleteUrl}/${recordId}`, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
      });

      // 2. Fallback: POST /articles {id: X}
      if (response.status === 405 || response.status === 404) {
        response = await fetch(modalDeleteUrl, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: recordId }),
        });
      }

      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        // Odświeżanie DataTables
        const container = document.querySelector(".datatableContainer");
        const mode = (container?.getAttribute("data-dt-mode") || "").toUpperCase();

        if (mode === "API" && window.__DBM_DT_API__?.refresh) {
          window.__DBM_DT_API__.refresh(container);
        } else if (window.dbmDataTable?.ajax?.reload) {
          window.dbmDataTable.ajax.reload(null, false);
        } else {
          window.location.reload();
        }

        bootstrap.Modal.getInstance(deleteModal)?.hide();
      } else {
        console.error("ERROR:", result.message || "Unknown error");
      }
    } catch (err) {
      console.error("AJAX ERROR:", err.message);
    }
  });
});
