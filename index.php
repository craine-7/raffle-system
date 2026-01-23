<?php
session_start();
include 'config/db.php';

$bg = $conn->query("SELECT background FROM settings WHERE id=1")->fetch_assoc()['background'];

$participants = $conn->query("SELECT * FROM participants WHERE status='active'");

$last_winners = $_SESSION['last_winners'] ?? [];
$last_prize = $_SESSION['last_prize'] ?? '';

// Get available background images
$bg_images = [];
$bg_dir = 'assets/bg/';
if (is_dir($bg_dir)) {
    $files = scandir($bg_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $bg_images[] = $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raffle Draw System | Win Amazing Prizes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Inject the background image dynamically */
        body {
            background: url('<?php echo htmlspecialchars($bg); ?>') no-repeat center center fixed !important;
            background-size: cover !important;
        }
    
        .badge.bg-success {
         background: var(--success-gradient) !important;
         color: white;
         padding: 8px 12px;
         font-size: 0.9rem;
         border-radius: 10px;
       }
     
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div id="particles"></div>

    <!-- Settings Button -->
    <button class="btn settings-btn" type="button" data-bs-toggle="modal" data-bs-target="#settingsModal">
        <i class="fas fa-cog"></i>
    </button>

    <div class="main-container">
        <!-- Header -->
        <div class="brand-header">
            <h1>RAFFLE DRAW SYSTEM</h1>
            <p class="tagline">Win Amazing Prizes • Fair & Transparent • Instant Results</p>
        </div>

<!-- Success Alert -->
<?php if(!empty($last_winners) && isset($_SESSION['last_winners'])): ?>
<div class="alert success-alert alert-dismissible fade show" role="alert">
    <div class="d-flex justify-content-center">
        <div class="text-center">
            <div class="mb-3">
                <i class="fas fa-trophy fa-3x" style="color: #ffc107;"></i>
            </div>
            <h5 class="mb-2">🎉 Congratulations to Our Winners! 🎉</h5>
            <p class="mb-2">Prize: <strong class="text-warning">"<?php echo htmlspecialchars($last_prize); ?>"</strong></p>
            <div class="mb-3">
                <?php foreach($last_winners as $winner): ?>
                    <span class="winner-badge"><?php echo htmlspecialchars($winner); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php 
    unset($_SESSION['last_winners']);
    unset($_SESSION['last_prize']);
?>
<?php endif; ?>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-6">
                <!-- Add Participant -->
                <div class="add-form glass-card">
                    <h5 class="mb-3"><i class="fas fa-user-plus me-2"></i> Add New Participant</h5>
                    <form action="actions/add_participant.php" method="post" class="d-flex gap-2">
                        <input type="text" name="fullname" class="form-control" placeholder="Enter participant name..." required>
                        <button class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Add
                        </button>
                    </form>
                </div>

                <!-- Active Participants -->
                <div class="glass-card">
                    <div class="card-header">
                        <h5><i class="fas fa-users me-2"></i> Active Participants</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            $participants_result = $conn->query("SELECT * FROM participants WHERE status='active'");
                            if($participants_result->num_rows > 0): 
                                while($row = $participants_result->fetch_assoc()): ?>
                                    <div class="participant-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle me-3 fa-lg"></i>
                                            <span class="fs-5"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; 
                            else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users-slash fa-3x mb-3" style="color: rgba(255,255,255,0.3);"></i>
                                    <p class="text-muted">No active participants available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-white-50">
                            Total: <strong class="text-white"><?php echo $participants_result->num_rows; ?></strong> active participant(s)
                        </small>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6">
                <!-- Main Draw Button -->
                <div class="draw-btn-container">
                    <button class="btn draw-main-btn" data-bs-toggle="modal" data-bs-target="#raffleModal">
                        <i class="fas fa-gift me-3"></i> DRAW RAFFLE
                    </button>
                    <p class="text-white-50 mt-3">Click to start an exciting raffle draw!</p>
                </div>

                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3><?php 
                            $active_count = $conn->query("SELECT COUNT(*) as count FROM participants WHERE status='active'")->fetch_assoc()['count'];
                            echo $active_count;
                            ?></h3>
                            <small>ACTIVE PARTICIPANTS</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3><?php 
                            $total_winners = $conn->query("SELECT COUNT(*) as count FROM winners")->fetch_assoc()['count'];
                            echo $total_winners;
                            ?></h3>
                            <small>TOTAL WINNERS</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Winners -->
                <div class="glass-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i> Recent Winners</h5>
                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#winnersHistoryModal">
                            <i class="fas fa-history me-1"></i> View All
                        </button>
                    </div>
                    <div class="card-body">
                        <?php 
                        $recent_winners = $conn->query("SELECT * FROM winners ORDER BY win_date DESC LIMIT 5");
                        if($recent_winners->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($winner = $recent_winners->fetch_assoc()): 
                                    $win_date = strtotime($winner['win_date']);
                                    $date_formatted = date('M d, Y', $win_date);
                                    $time_formatted = date('h:i A', $win_date);
                                ?>
                                    <div class="list-group-item border-0 bg-transparent mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-white"><?php echo htmlspecialchars($winner['fullname']); ?></strong>
                                                <div class="winner-date">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo $date_formatted; ?>
                                                    <span class="winner-time ms-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $time_formatted; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($winner['prize']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-gift fa-3x mb-3" style="color: rgba(255,255,255,0.3);"></i>
                                <p class="text-white-50">No winners yet. Be the first to draw!</p>
                            </div>
                        <?php endif; ?>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Raffle Modal -->
    <div class="modal fade" id="raffleModal" tabindex="-1" aria-labelledby="raffleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h2 class="modal-title" id="raffleModalLabel">
                        <i class="fas fa-gift me-2"></i> Raffle Draw
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/draw_raffle.php" method="post">
                    <div class="modal-body">
                        <!-- Prize Input -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">🎁 Prize Name</label>
                            <input type="text" name="prize" class="form-control form-control-lg" 
                                   placeholder="e.g., Electric Fan, Gift Certificate, Cash Prize..." required>
                        </div>

                        <!-- Number of Winners -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">👑 Number of Winners</label>
                            <div class="input-group input-group-lg">
                                <button class="btn btn-outline-secondary" type="button" id="decreaseBtn">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="winner_count" 
                                       class="form-control text-center fw-bold" 
                                       id="winnerCountInput"
                                       min="1" 
                                       max="50" 
                                       value="1"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="increaseBtn">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <!-- Quick Selection -->
                            <div class="mt-3">
                                <small class="text-muted">Quick select:</small>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php foreach([1, 3, 5, 10, 15] as $num): ?>
                                        <button type="button" class="btn btn-outline-primary winner-quick-btn" data-value="<?php echo $num; ?>">
                                            <?php echo $num; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Participants Info -->
                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Currently <strong id="activeCount"><?php echo $active_count; ?></strong> active participant(s) available.
                                    Max: <strong id="maxWinners"><?php echo min(50, $active_count); ?></strong> winner(s)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-lg px-5" <?php echo $active_count == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-play me-2"></i> Start Draw
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade settings-modal" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-palette me-2"></i> Background Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload Background -->
                    <form action="actions/upload_bg.php" method="post" enctype="multipart/form-data" class="mb-4">
                        <label class="form-label fw-bold">Upload New Background</label>
                        <div id="drop-area" class="border-2 border-dashed rounded-3 p-4 text-center mb-3">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-3 text-primary"></i>
                            <p class="mb-2">Drag & drop or click to upload</p>
                            <p class="text-muted small mb-0">Supports: JPG, PNG, WebP</p>
                            <input type="file" name="background" id="bgInput" accept="image/*" hidden>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="document.getElementById('bgInput').click()">
                                Choose Image
                            </button>
                            <p id="file-name" class="mt-2 mb-0 text-success small"></p>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload Background
                        </button>
                    </form>

                    <!-- Select Background -->
                    <?php if(!empty($bg_images)): ?>
                    <form action="actions/change_bg.php" method="post" id="bgSelectForm">
                        <label class="form-label fw-bold">Select Background</label>
                        <select name="bg" class="form-select mb-3" id="bgSelect">
                            <option value="">Choose a background...</option>
                            <?php foreach($bg_images as $image): 
                                $full_path = "assets/bg/" . $image;
                                $is_current = ($bg == $full_path);
                            ?>
                                <option value="<?php echo $full_path; ?>" <?php echo $is_current ? 'selected' : ''; ?>>
                                    <?php echo $image; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="text-center mb-3">
                            <div class="bg-preview rounded-3" id="bgPreview" 
                                 style="background-image: url('<?php echo htmlspecialchars($bg); ?>'); height: 150px;"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check me-2"></i>Apply Background
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No background images found. Upload one to get started!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Winners History Modal -->
    <div class="modal fade" id="winnersHistoryModal" tabindex="-1" aria-labelledby="winnersHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="winnersHistoryModalLabel">
                        <i class="fas fa-history me-2"></i> All Winners History
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <?php 
                    $all_winners = $conn->query("SELECT * FROM winners ORDER BY win_date DESC");
                    if($all_winners->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 winners-table">
                                <thead>
                                    <tr>
                                        <th width="30%">Winner</th>
                                        <th width="35%">Prize</th>
                                        <th width="35%">Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($winner = $all_winners->fetch_assoc()): 
                                        $win_date = strtotime($winner['win_date']);
                                        $date_formatted = date('M d, Y', $win_date);
                                        $time_formatted = date('h:i A', $win_date);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                                    <strong><?php echo htmlspecialchars($winner['fullname']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo htmlspecialchars($winner['prize']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div>
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo $date_formatted; ?>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $time_formatted; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-trophy fa-4x mb-3" style="color: #ddd;"></i>
                            <h5 class="text-muted">No winners history available</h5>
                            <p class="text-muted">Start drawing raffles to see winners here!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <div>
                            <span class="text-muted">
                                Total Winners: <strong><?php echo $total_winners; ?></strong>
                            </span>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Create floating particles
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        for(let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + 'vw';
            particle.style.width = Math.random() * 4 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDelay = Math.random() * 20 + 's';
            particle.style.opacity = Math.random() * 0.5 + 0.2;
            particlesContainer.appendChild(particle);
        }
    }

    // Raffle modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        createParticles();
        
        const winnerCountInput = document.getElementById('winnerCountInput');
        const decreaseBtn = document.getElementById('decreaseBtn');
        const increaseBtn = document.getElementById('increaseBtn');
        const quickButtons = document.querySelectorAll('.winner-quick-btn');
        const activeCount = document.getElementById('activeCount');
        
        if(winnerCountInput && activeCount) {
            const maxParticipants = parseInt(activeCount.textContent);
            const currentMax = Math.min(50, maxParticipants);
            
            winnerCountInput.max = currentMax;
            
            // Update quick button states
            function updateWinnerOptions() {
                const currentValue = parseInt(winnerCountInput.value);
                
                // Update quick buttons
                quickButtons.forEach(button => {
                    const btnValue = parseInt(button.getAttribute('data-value'));
                    
                    if(btnValue === currentValue) {
                        button.classList.add('active');
                        button.classList.remove('btn-outline-primary');
                        button.classList.add('btn-primary');
                    } else {
                        button.classList.remove('active');
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-outline-primary');
                    }
                    
                    if(btnValue > currentMax) {
                        button.disabled = true;
                        button.classList.add('disabled');
                    } else {
                        button.disabled = false;
                        button.classList.remove('disabled');
                    }
                });
                
                // Update increase/decrease buttons
                if(decreaseBtn) decreaseBtn.disabled = currentValue <= 1;
                if(increaseBtn) increaseBtn.disabled = currentValue >= currentMax;
            }
            
            // Event listeners
            decreaseBtn?.addEventListener('click', () => {
                let val = parseInt(winnerCountInput.value);
                if(val > 1) winnerCountInput.value = val - 1;
                updateWinnerOptions();
            });
            
            increaseBtn?.addEventListener('click', () => {
                let val = parseInt(winnerCountInput.value);
                if(val < currentMax) winnerCountInput.value = val + 1;
                updateWinnerOptions();
            });
            
            quickButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    if(value <= currentMax) {
                        winnerCountInput.value = value;
                        updateWinnerOptions();
                    }
                });
            });
            
            winnerCountInput.addEventListener('input', updateWinnerOptions);
            updateWinnerOptions();
        }
        
        // File upload preview - using CSS classes instead of inline styles
        const dropArea = document.getElementById('drop-area');
        const bgInput = document.getElementById('bgInput');
        const fileName = document.getElementById('file-name');
        const bgSelect = document.getElementById('bgSelect');
        const bgPreview = document.getElementById('bgPreview');
        
        if(dropArea && bgInput) {
            ['dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            dropArea.addEventListener('dragover', () => {
                dropArea.classList.add('dragover');
            });
            
            dropArea.addEventListener('dragleave', () => {
                dropArea.classList.remove('dragover');
            });
            
            dropArea.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                bgInput.files = files;
                fileName.textContent = `Selected: ${files[0].name}`;
                dropArea.classList.remove('dragover');
            });
            
            bgInput.addEventListener('change', () => {
                if(bgInput.files[0]) {
                    fileName.textContent = `Selected: ${bgInput.files[0].name}`;
                }
            });
        }
        
        if(bgSelect && bgPreview) {
            bgSelect.addEventListener('change', function() {
                if(this.value) {
                    bgPreview.style.backgroundImage = `url('${this.value}')`;
                }
            });
        }
        
        // Confirmation for draw
        const raffleForm = document.querySelector('form[action="actions/draw_raffle.php"]');
        if(raffleForm) {
            raffleForm.addEventListener('submit', function(e) {
                const prize = this.querySelector('input[name="prize"]').value;
                const winnerCount = this.querySelector('#winnerCountInput').value;
                const activeCount = parseInt(document.getElementById('activeCount').textContent);
                
                if(activeCount === 0) {
                    e.preventDefault();
                    alert('Please add participants before drawing!');
                    return;
                }
                
                if(!confirm(`Draw ${winnerCount} winner(s) for "${prize}"?`)) {
                    e.preventDefault();
                }
            });
        }
        
        // Add some fun animations
        const cards = document.querySelectorAll('.glass-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Initialize Winners History Modal Search
        const winnersHistoryModal = document.getElementById('winnersHistoryModal');
        if(winnersHistoryModal) {
            winnersHistoryModal.addEventListener('shown.bs.modal', function() {
                // Add search functionality if needed
                console.log('Winners history modal opened');
            });
        }
    });
    
    // Search functionality for winners history
    function searchWinners() {
        const input = document.getElementById('searchWinners');
        const filter = input.value.toUpperCase();
        const table = document.querySelector('.winners-table');
        const tr = table.getElementsByTagName('tr');
        
        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName('td')[0];
            const tdPrize = tr[i].getElementsByTagName('td')[1];
            if (tdName || tdPrize) {
                const nameText = tdName.textContent || tdName.innerText;
                const prizeText = tdPrize.textContent || tdPrize.innerText;
                if (nameText.toUpperCase().indexOf(filter) > -1 || prizeText.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    }
    </script>
</body>
</html>