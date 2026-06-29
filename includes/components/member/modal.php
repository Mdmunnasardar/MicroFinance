<!-- Quick View Modal -->
<div id="quickViewModal" class="member-modal">
    <div class="modal-content" id="quickViewContent">
        <!-- Content loaded via JavaScript -->
    </div>
</div>

<style>
.member-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}
.member-modal.active {
    display: flex;
}
.member-modal .modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s ease;
}
.member-modal .modal-content.active {
    transform: scale(1);
    opacity: 1;
}
</style>