<div class="card qa" id="qa">
  <div class="section-header">
    <div>
      <h2 class="panel-title">Quản lý câu hỏi / đáp (Q &amp; A)</h2>
      <div class="section-note">Hiển thị toàn bộ câu hỏi trong database, thêm mới, cập nhật và xoá trực tiếp ngay tại đây.</div>
    </div>
    <button class="btn btn-accent" id="qa-add-toggle">+ Thêm câu hỏi mới</button>
  </div>

  <?php if ($qaMessage !== ''): ?>
    <div class="qa-alert <?php echo $qaMessageType === 'error' ? 'qa-alert-error' : 'qa-alert-success'; ?>">
      <?php echo h($qaMessage); ?>
    </div>
  <?php endif; ?>

  <div class="qa-admin-box" id="qa-add-box">
    <form method="POST" class="qa-add-form">
      <input type="hidden" name="qa_action" value="add">

      <div class="qa-form-grid">
        <div class="field">
          <label for="qa_add_title">Title</label>
          <input id="qa_add_title" type="text" name="title" placeholder="Nhập tiêu đề câu hỏi" required>
        </div>

        <div class="field">
          <label>Role</label>
          <input type="text" value="admin" readonly>
        </div>

        <div class="field">
          <label for="qa_add_category">Category</label>
          <select id="qa_add_category" name="category" required>
            <?php foreach ($qaCategories as $category): ?>
              <option value="<?php echo h($category); ?>"><?php echo ucfirst(h($category)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="qa_add_status">Status</label>
          <select id="qa_add_status" name="status" required>
            <?php foreach ($qaStatuses as $status): ?>
              <option value="<?php echo h($status); ?>"><?php echo strtoupper(h($status)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field-full">
          <label for="qa_add_content">Content</label>
          <textarea id="qa_add_content" name="content" placeholder="Nhập nội dung câu trả lời hoặc mô tả chi tiết" required></textarea>
        </div>
      </div>

      <div class="bottom-actions qa-actions-right">
        <button type="submit" class="btn btn-accent">Xác nhận thêm</button>
      </div>
    </form>
  </div>

  <div class="table-wrap qa-table-wrap">
    <table>
      <thead>
        <tr>
          <th style="min-width: 70px;">ID</th>
          <th style="min-width: 220px;">Title</th>
          <th style="min-width: 160px;">Category</th>
          <th style="min-width: 140px;">Status</th>
          <th style="min-width: 120px;">Role</th>
          <th style="min-width: 340px;">Content</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($qaRows)): ?>
          <?php foreach ($qaRows as $qaRow): ?>
            <?php $rowFormId = 'qa_form_' . (int)$qaRow['q_id']; ?>
            <tr>
              <td>
                <form id="<?php echo h($rowFormId); ?>" method="POST">
                  <input type="hidden" name="q_id" value="<?php echo (int)$qaRow['q_id']; ?>">
                </form>
                <strong>#<?php echo (int)$qaRow['q_id']; ?></strong>
              </td>
              <td>
                <input form="<?php echo h($rowFormId); ?>" type="text" name="title" value="<?php echo h($qaRow['title']); ?>" required>
              </td>
              <td>
                <select form="<?php echo h($rowFormId); ?>" name="category" required>
                  <?php foreach ($qaCategories as $category): ?>
                    <option value="<?php echo h($category); ?>" <?php echo $qaRow['category'] === $category ? 'selected' : ''; ?>>
                      <?php echo ucfirst(h($category)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select form="<?php echo h($rowFormId); ?>" name="status" required>
                  <?php foreach ($qaStatuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo $qaRow['status'] === $status ? 'selected' : ''; ?>>
                      <?php echo strtoupper(h($status)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <span class="badge <?php echo $qaRow['role'] === 'admin' ? 'live' : 'draft'; ?>"><?php echo ucfirst(h($qaRow['role'])); ?></span>
              </td>
              <td>
                <textarea form="<?php echo h($rowFormId); ?>" name="content" class="qa-table-textarea" required><?php echo h($qaRow['content']); ?></textarea>
              </td>
            </tr>
            <tr class="qa-action-row">
              <td colspan="6">
                <div class="action-cell qa-action-cell">
                  <button form="<?php echo h($rowFormId); ?>" type="submit" name="qa_action" value="update" class="btn btn-light">Lưu</button>
                  <button form="<?php echo h($rowFormId); ?>" type="submit" name="qa_action" value="delete" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn xoá câu hỏi này?');">Xoá</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="qa-empty-state">Chưa có câu hỏi nào trong database.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('qa-add-toggle').addEventListener('click', function () {
    const form = document.querySelector('#qa-add-box .qa-add-form');
    const isOpen = form.classList.contains('open');
    form.classList.toggle('open');
    this.textContent = isOpen ? '+ Thêm câu hỏi mới' : '✕ Đóng form';
  });
</script>
