
path = r'c:\xampp\htdocs\LYDO\lydo-system\admin2\event_qr.php'
c = open(path, encoding='utf-8').read()

# Find the section between "Rotating code display" and the qr-url div
start_marker = '    <!-- Rotating code display -->'
end_marker   = '    <div class="qr-url">'

start_idx = c.find(start_marker)
end_idx   = c.find(end_marker)

if start_idx < 0 or end_idx < 0:
    print('MARKERS NOT FOUND')
    print('start:', start_idx, 'end:', end_idx)
else:
    new_section = '''    <!-- Check-in Code (available start_time to start_time+30min) -->
    <div style="margin:10px 0;padding:12px;background:#f8fafc;border-radius:10px;border:1.5px solid <?= $checkinAvailable ? '#a5d6a7' : '#e2e8f0' ?>">
      <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">
        <i class="fas fa-sign-in-alt" style="color:#2e7d32"></i> Check-in Code
      </div>
      <?php if ($checkinAvailable): ?>
        <div style="font-family:monospace;font-size:1.8rem;font-weight:900;letter-spacing:.3em;color:#1b5e20" id="liveCode"><?= htmlspecialchars($currentCode) ?></div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:5px">
          <i class="fas fa-clock"></i> Code changes in <span id="codeCountdown" style="font-weight:700;color:#f57f17"><?= $secsLeft ?>s</span>
        </div>
        <?php if ($secsUntilCheckinClose > 0): ?>
        <div style="font-size:.72rem;color:#c62828;margin-top:4px;font-weight:600">
          <i class="fas fa-exclamation-circle"></i> Check-in closes in <span id="checkinCloseCountdown"><?= gmdate('i:s', $secsUntilCheckinClose) ?></span>
        </div>
        <?php endif; ?>
        <div style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:8px;overflow:hidden">
          <div id="codeProgress" style="height:100%;background:linear-gradient(90deg,#2e7d32,#43a047);border-radius:2px;transition:width 1s linear;width:<?= round(($secsLeft/30)*100) ?>%"></div>
        </div>
      <?php elseif ($startTime && $now < $startTime): ?>
        <div style="font-size:.85rem;color:#94a3b8;padding:6px 0">
          <i class="fas fa-hourglass-half"></i> Opens at <strong><?= date('g:i A', strtotime($startTime)) ?></strong>
        </div>
      <?php elseif ($checkinDeadline && $now >= $checkinDeadline): ?>
        <div style="font-size:.85rem;color:#c62828;padding:6px 0;font-weight:600">
          <i class="fas fa-lock"></i> Check-in closed (30-min window expired)
        </div>
      <?php else: ?>
        <div style="font-size:.82rem;color:#94a3b8;padding:6px 0">Set event start time to enable check-in code</div>
      <?php endif; ?>
    </div>

    <!-- Check-out Code (available from event_end_time onwards) -->
    <div style="margin:8px 0;padding:12px;background:#f8fafc;border-radius:10px;border:1.5px solid <?= $checkoutAvailable ? '#ef9a9a' : '#e2e8f0' ?>">
      <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">
        <i class="fas fa-sign-out-alt" style="color:#c62828"></i> Check-out Code
      </div>
      <?php if ($checkoutAvailable): ?>
        <div style="font-family:monospace;font-size:1.8rem;font-weight:900;letter-spacing:.3em;color:#b71c1c" id="liveCodeOut"><?= htmlspecialchars($checkoutCode) ?></div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:5px">
          <i class="fas fa-clock"></i> Changes in <span id="codeCountdownOut" style="font-weight:700;color:#f57f17"><?= $secsLeft ?>s</span>
        </div>
        <div style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:8px;overflow:hidden">
          <div id="codeProgressOut" style="height:100%;background:linear-gradient(90deg,#c62828,#ef5350);border-radius:2px;transition:width 1s linear;width:<?= round(($secsLeft/30)*100) ?>%"></div>
        </div>
      <?php elseif ($endTime): ?>
        <div style="font-size:.85rem;color:#94a3b8;padding:6px 0">
          <i class="fas fa-hourglass-half"></i> Available at <strong><?= date('g:i A', strtotime($endTime)) ?></strong>
          <?php if ($secsUntilEnd > 0): ?>
            <span style="margin-left:6px;background:#fff8e1;color:#f57f17;padding:2px 8px;border-radius:5px;font-size:.75rem;font-weight:700">
              in <span id="endCountdown"><?= gmdate('i:s', $secsUntilEnd) ?></span>
            </span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div style="font-size:.82rem;color:#94a3b8;padding:6px 0">Set event end time to enable check-out code</div>
      <?php endif; ?>
    </div>

    '''

    c = c[:start_idx] + new_section + c[end_idx:]
    open(path, 'w', encoding='utf-8').write(c)
    print('Done! File updated successfully.')
    print('New section length:', len(new_section))
