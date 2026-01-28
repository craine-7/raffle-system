<?php
session_start();
// Store the current event in session for form redirects
if(isset($_GET['event'])) {
    $_SESSION['last_event_id'] = intval($_GET['event']);
} elseif(!isset($_SESSION['last_event_id'])) {
    $_SESSION['last_event_id'] = 1;
}

// Use session event if not in URL
if(!isset($_GET['event']) && isset($_SESSION['last_event_id'])) {
    $current_event_id = $_SESSION['last_event_id'];
} else {
    $current_event_id = isset($_GET['event']) ? intval($_GET['event']) : 1;
}

include 'config/db.php';

$bg = $conn->query("SELECT background FROM settings WHERE id=1")->fetch_assoc()['background'];

// Get selected event for display (default to event 1)
$current_event_id = isset($_GET['event']) ? intval($_GET['event']) : 1;

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

// Get active participants count for current event
$active_count = $conn->query("SELECT COUNT(*) as count FROM participants WHERE status='active' AND event_id = $current_event_id")->fetch_assoc()['count'];
$total_winners = $conn->query("SELECT COUNT(*) as count FROM winners")->fetch_assoc()['count'];

// Get today's winners count for current event
$today = date('Y-m-d');
$daily_winners_count = $conn->query("SELECT COUNT(*) as count FROM winners WHERE DATE(win_date) = '$today' AND event_id = $current_event_id")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raffle Draw System | Win Amazing Prizes</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎁</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
     
        /* Event session card styling */
        .event-session-card {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .current-event-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Event switcher */
        .event-switcher {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 999;
        }
        
        .event-switcher .btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 500;
        }
        
        .event-switcher .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Add to the existing style section */
.sound-toggle-btn {
    position: fixed;
    top: 20px;
    right: 80px;
    z-index: 1000;
    background: var(--primary-gradient);
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.sound-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 15px 30px rgba(102, 126, 234, 0.6);
}

.sound-toggle-btn.muted {
    background: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.7);
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

    <!-- Add this after the settings button in index.php -->
<button class="btn sound-toggle-btn" id="soundToggleBtn" title="Toggle Sound">
    <i class="fas fa-volume-up"></i>
</button>

<audio id="winnerSound" preload="auto" hidden>
    <source src="assets/sounds/winner_sound.mp3" type="audio/mpeg">
</audio>

    <!-- Event Switcher -->
    <div class="event-switcher">
        <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-calendar-alt me-2"></i>
                <?php 
                $current_event = $conn->query("SELECT name FROM events WHERE id = $current_event_id")->fetch_assoc();
                echo htmlspecialchars($current_event['name'] ?? 'Select Event');
                ?>
            </button>
            <ul class="dropdown-menu">
                <?php 
                $events = $conn->query("SELECT * FROM events WHERE is_active = 1");
                while($event = $events->fetch_assoc()): 
                ?>
                    <li>
                        <a class="dropdown-item <?php echo $event['id'] == $current_event_id ? 'active' : ''; ?>" 
                           href="?event=<?php echo $event['id']; ?>">
                            <?php echo htmlspecialchars($event['name']); ?>
                            <?php if($event['id'] == $current_event_id): ?>
                                <i class="fas fa-check ms-2 text-success"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endwhile; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#eventsModal">
                        <i class="fas fa-plus me-2"></i> Manage Events
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="main-container">
        <!-- Header -->
        <div class="brand-header">
            <h1>RAFFLE DRAW SYSTEM</h1>
            <p class="tagline">Win Amazing Prizes • Fair & Transparent • Instant Results</p>
            
            <!-- Current Event Session -->
            <div class="event-session-card mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-white mb-1">Current Session</h5>
                        <div class="current-event-badge d-inline-block">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo htmlspecialchars($current_event['name'] ?? 'Default Event'); ?>
                        </div>
                    </div>
                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#eventsModal">
                        <i class="fas fa-cog me-2"></i> Manage Events
                    </button>
                </div>
            </div>
        </div>

        <!-- Winner Popup Modal -->
        <?php if(!empty($last_winners) && isset($_SESSION['last_winners'])): ?>
        <?php 
        // Get event name for display
        $event_id = $_SESSION['last_event_id'] ?? 1;
        $event_name = $conn->query("SELECT name FROM events WHERE id = $event_id")->fetch_assoc()['name'];
        ?>
        <div class="modal fade" id="winnerPopup" tabindex="-1" aria-labelledby="winnerPopupLabel" aria-hidden="false" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content winner-popup">
                    <div class="modal-header border-0 bg-warning text-center">
                        <h2 class="modal-title text-white w-100" id="winnerPopupLabel">
                            🎉 CONGRATULATIONS! 🎉
                        </h2>
                    </div>
                    <div class="modal-body text-center py-5">
                        <div class="winner-icon mb-4">
                            <i class="fas fa-trophy fa-5x text-warning"></i>
                        </div>
                        <h4 class="mb-2 text-dark">Event: <span class="text-primary"><?php echo htmlspecialchars($event_name); ?></span></h4>
                        <h3 class="mb-3 text-dark">Prize: <span class="text-primary">"<?php echo htmlspecialchars($last_prize); ?>"</span></h3>
                        <div class="winners-list mb-4">
                            <h4 class="mb-3 text-dark">🏆 WINNERS 🏆</h4>
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <?php 
                                $index = 0;
                                foreach($last_winners as $winner): 
                                ?>
                                    <div class="winner-name-badge animate__animated animate__bounceIn" style="--item-index: <?php echo $index++; ?>">
                                        <i class="fas fa-crown me-2 text-warning"></i>
                                        <span class="fs-4 fw-bold"><?php echo htmlspecialchars($winner); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="celebration-text">
                            <p class="fs-5 text-muted">Congratulations to all winners! 🎊</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" class="btn btn-lg btn-success" data-bs-dismiss="modal" onclick="closeWinnerPopup()">
                            <i class="fas fa-check me-2"></i> Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const winnerPopup = new bootstrap.Modal(document.getElementById('winnerPopup'));
                winnerPopup.show();
                
                // Start confetti
                startConfetti();
                
                // Auto-close after 10 seconds
                // setTimeout(() => {
                //     if(document.getElementById('winnerPopup').classList.contains('show')) {
                //         closeWinnerPopup();
                //     }
                // }, 10000);
            });
            
            function startConfetti() {
                // Multiple confetti bursts
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
                
                setTimeout(() => {
                    confetti({
                        particleCount: 150,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0 }
                    });
                }, 250);
                
                setTimeout(() => {
                    confetti({
                        particleCount: 150,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 }
                    });
                }, 500);
                
                // Continuous confetti
                const duration = 8 * 1000;
                const animationEnd = Date.now() + duration;
                const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };
                
                function randomInRange(min, max) {
                    return Math.random() * (max - min) + min;
                }
                
                const interval = setInterval(function() {
                    const timeLeft = animationEnd - Date.now();
                    
                    if (timeLeft <= 0) {
                        return clearInterval(interval);
                    }
                    
                    const particleCount = 50 * (timeLeft / duration);
                    
                    confetti(Object.assign({}, defaults, {
                        particleCount,
                        origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
                    }));
                    confetti(Object.assign({}, defaults, {
                        particleCount,
                        origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
                    }));
                }, 250);
            }
            
function closeWinnerPopup() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('winnerPopup'));
    modal.hide();
    // Stop any ongoing confetti
    confetti.reset();
}
        </script>

        <style>
            .winner-popup .modal-content {
                animation: winnerReveal 1.5s ease-out;
                border: 3px solid gold;
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
            }
            
            @keyframes winnerReveal {
                0% {
                    transform: scale(0.5) rotate(-10deg);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.1) rotate(5deg);
                }
                100% {
                    transform: scale(1) rotate(0);
                    opacity: 1;
                }
            }
            
            .winner-name-badge {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 25px;
                border-radius: 15px;
                margin: 10px;
                animation-delay: calc(var(--item-index) * 0.2s);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                transition: transform 0.3s;
            }
            
            .winner-name-badge:hover {
                transform: translateY(-5px) scale(1.05);
            }
            
            .winner-icon {
                animation: bounce 2s infinite;
            }
            
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
        </style>
        <?php 
            unset($_SESSION['last_winners']);
            unset($_SESSION['last_prize']);
            unset($_SESSION['last_event_id']);
        endif; 
        ?>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-6">
                <!-- Combined Add Participants Container -->
                <div class="glass-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Add Participants to Event</h5>
                    </div>
                    <div class="card-body">
                        <!-- Tabs for different add methods -->
                        <ul class="nav nav-tabs mb-3" id="addParticipantsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button">
                                    <i class="fas fa-user me-2"></i> Single
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" type="button">
                                    <i class="fas fa-users me-2"></i> Bulk Upload
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Tab content -->
                        <div class="tab-content" id="addParticipantsTabContent">
                            <!-- Single Add Tab -->
                            <div class="tab-pane fade show active" id="single" role="tabpanel">
                                <form action="actions/add_participant.php" method="post">
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="text" name="fullname" class="form-control" placeholder="Enter participant name..." required>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="event_id" class="form-select" required>
                                                <?php 
                                                $events = $conn->query("SELECT * FROM events WHERE is_active = 1");
                                                while($event = $events->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $event['id']; ?>" 
                                                            <?php echo $event['id'] == $current_event_id ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($event['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-success w-100">
                                                <i class="fas fa-plus me-2"></i>Add Single Participant
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Bulk Upload Tab -->
<div class="tab-pane fade" id="bulk" role="tabpanel">
    <form action="actions/upload_participants.php" method="post" enctype="multipart/form-data" id="bulkUploadForm">
        <div class="mb-3">
            <label class="form-label small">Select Event for Upload</label>
            <select name="event_id" class="form-select">
                <?php 
                $events = $conn->query("SELECT * FROM events WHERE is_active = 1");
                while($event = $events->fetch_assoc()): 
                ?>
                    <option value="<?php echo $event['id']; ?>" 
                            <?php echo $event['id'] == $current_event_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($event['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label small">Drag & Drop or Select CSV/Excel File</label>
            <div class="file-drop-area" id="fileDropArea">
                <div class="file-drop-content">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                    <h5 class="mb-2">Drag & Drop your file here</h5>
                    <p class="text-muted small mb-3">or click to browse</p>
                    <div class="file-types">
                        <span class="badge bg-primary me-1">CSV</span>
                        <span class="badge bg-success me-1">XLS</span>
                        <span class="badge bg-warning">XLSX</span>
                    </div>
                    <input type="file" name="participants_file" id="participantsFile" 
                           class="file-input" accept=".csv,.xls,.xlsx" hidden>
                </div>
                <div class="file-preview mt-3" id="filePreview" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between bg-light rounded p-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file-excel text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0" id="fileName">filename.csv</h6>
                                <small class="text-muted" id="fileSize">0 KB</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger" id="removeFileBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="progress mt-2" style="height: 5px; display: none;" id="uploadProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <small class="text-muted">Upload CSV or Excel file with one name per line</small>
        </div>
        <button class="btn btn-primary w-100" type="submit" id="uploadBtn" disabled>
            <i class="fas fa-upload me-2"></i>Upload Participants
        </button>
    </form>
</div>
                        </div>
                    </div>
                </div>

                <!-- Active Participants -->
                <div class="glass-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-users me-2"></i> Active Participants</h5>
                        <span class="badge bg-primary">Event: <?php echo htmlspecialchars($current_event['name'] ?? 'Default'); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            $participants_result = $conn->query("SELECT * FROM participants WHERE status='active' AND event_id = $current_event_id");
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
                                    <p class="text-muted">No active participants available for this event.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-white-50">
                            Total: <strong class="text-white"><?php echo $participants_result->num_rows; ?></strong> active participant(s) in this event
                        </small>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3><?php echo $active_count; ?></h3>
                            <small>ACTIVE PARTICIPANTS</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3><?php echo $daily_winners_count; ?></h3>
                            <small>TODAY'S WINNERS</small>
                        </div>
                    </div>
                </div>

                <!-- Main Draw Button -->
                <div class="draw-btn-container mb-4">
                    <button class="btn draw-main-btn" data-bs-toggle="modal" data-bs-target="#raffleModal">
                        <i class="fas fa-gift me-3"></i> DRAW RAFFLE
                    </button>
                    <p class="text-white-50 mt-3">Click to start an exciting raffle draw for current event!</p>
                </div>

                <!-- Recent Winners (Daily) -->
                <div class="glass-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i> Today's Winners</h5>
                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#winnersHistoryModal">
                            <i class="fas fa-history me-1"></i> View All
                        </button>
                    </div>
                    <div class="card-body">
                        <?php 
                        $daily_winners = $conn->query("
                            SELECT w.*, e.name as event_name 
                            FROM winners w
                            LEFT JOIN events e ON w.event_id = e.id
                            WHERE DATE(w.win_date) = '$today' 
                            AND w.event_id = $current_event_id
                            ORDER BY w.win_date DESC 
                            LIMIT 5
                        ");
                        
                        if($daily_winners->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($winner = $daily_winners->fetch_assoc()): 
                                    $win_date = strtotime($winner['win_date']);
                                    $time_formatted = date('h:i A', $win_date);
                                ?>
                                    <div class="list-group-item border-0 bg-transparent mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-white"><?php echo htmlspecialchars($winner['fullname']); ?></strong>
                                                <div class="winner-date">
                                                    <span class="winner-time">
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
                                <p class="text-white-50">No winners today for this event.</p>
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
                        <!-- Event Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">📅 Event Session</label>
                            <select name="event_id" class="form-select" required id="drawEventSelect">
                                <?php 
                                $events = $conn->query("SELECT * FROM events WHERE is_active = 1");
                                while($event = $events->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $event['id']; ?>" 
                                            <?php echo $event['id'] == $current_event_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['name']); ?>
                                        <?php if($event['token']): ?>
                                            (Token: <?php echo $event['token']; ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Different events have separate participant pools</small>
                        </div>

                        <!-- Prize Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">🏆 Prize Category</label>
                            <select name="prize_category" class="form-select" required>
                                <?php 
                                $categories = $conn->query("SELECT * FROM prize_categories");
                                while($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

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
                                <div class="alert alert-info" id="participantsInfo">
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
                        <button type="submit" class="btn btn-success btn-lg px-5" id="startDrawBtn" <?php echo $active_count == 0 ? 'disabled' : ''; ?>>
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
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="winnersHistoryModalLabel">
                    <i class="fas fa-history me-2"></i> Winners History
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchWinners" placeholder="Search winners or prizes...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- In the Winners History Modal section -->
<div class="dropdown">
    <button class="btn btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
        <i class="fas fa-file-export me-2"></i> Generate Report
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="#" onclick="generateReport('pdf')">
                <i class="fas fa-file-pdf me-2 text-danger"></i> PDF Report
            </a>
        </li>
    </ul>
</div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Date</label>
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Prize Category</label>
                        <select class="form-select" id="filterCategory">
                            <option value="">All Categories</option>
                            <?php 
                            $categories = $conn->query("SELECT * FROM prize_categories");
                            while($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Event</label>
                        <select class="form-select" id="filterEvent">
                            <option value="">All Events</option>
                            <?php 
                            $events = $conn->query("SELECT * FROM events");
                            while($event = $events->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $event['id']; ?>" <?php echo $event['id'] == $current_event_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Results per page</label>
                        <select class="form-select" id="resultsPerPage">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <!-- Winners Table -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0 winners-table" id="winnersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Winner</th>
                                <th>Prize</th>
                                <th>Category</th>
                                <th>Event</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody id="winnersTableBody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading winners...</p>
                </div>
                
                <!-- No Results Message -->
                <div id="noResults" class="text-center py-5" style="display: none;">
                    <i class="fas fa-trophy fa-3x mb-3 text-muted"></i>
                    <h5 class="text-muted">No winners found</h5>
                    <p class="text-muted">Try adjusting your filters or search terms</p>
                </div>
            </div>

                            <!-- Results Info -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info py-2 d-flex justify-content-between align-items-center">
                            <span id="resultsInfo">Loading winners...</span>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary" id="prevPage" disabled>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span class="px-3" id="pageInfo">Page 1 of 1</span>
                                <button class="btn btn-sm btn-outline-secondary" id="nextPage" disabled>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <div>
                        <span class="text-muted">
                            Total Winners: <strong id="totalWinners"><?php echo $total_winners; ?></strong>
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

<!-- Report Generation Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm" action="actions/generate_report.php" method="post" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Report Format</label>
                        <select name="format" class="form-select" required>
                            <option value="pdf">PDF Document</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event</label>
                        <select name="event_id" class="form-select" id="reportEventSelect">
                            <option value="">All Events</option>
                            <?php 
                            $events = $conn->query("SELECT * FROM events");
                            while($event = $events->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $event['id']; ?>" <?php echo $event['id'] == $current_event_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" name="start_date" class="form-control" placeholder="Start Date">
                            </div>
                            <div class="col">
                                <input type="date" name="end_date" class="form-control" placeholder="End Date">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include Columns</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="winner" checked>
                                    <label class="form-check-label">Winner Name</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="prize" checked>
                                    <label class="form-check-label">Prize</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="category" checked>
                                    <label class="form-check-label">Category</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="event" checked>
                                    <label class="form-check-label">Event</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="columns[]" value="date" checked>
                                    <label class="form-check-label">Date & Time</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort_by" class="form-select">
                            <option value="date_desc">Date (Newest First)</option>
                            <option value="date_asc">Date (Oldest First)</option>
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="prize_asc">Prize (A-Z)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReportForm()">
                    <i class="fas fa-download me-2"></i>Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Upload Help Modal -->
    <div class="modal fade" id="uploadHelpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i> File Format Help</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Accepted Formats:</strong></p>
                    <ul>
                        <li>CSV (.csv) - Comma separated values</li>
                        <li>Excel (.xls, .xlsx)</li>
                    </ul>
                    <p><strong>Format Requirements:</strong></p>
                    <ul>
                        <li>One participant name per row</li>
                        <li>First column should contain the name</li>
                        <li>No headers required</li>
                    </ul>
                    <p class="mb-0"><strong>Example CSV:</strong></p>
                    <pre class="bg-light p-2">John Doe
Jane Smith
Robert Johnson</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Management Modal -->
    <div class="modal fade" id="eventsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar me-2"></i> Event Management</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add New Event Form -->
                    <form action="actions/manage_events.php" method="post" class="mb-4">
                        <h6 class="mb-3">Add New Event</h6>
                        <div class="mb-3">
                            <label class="form-label">Event Name</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Token (Optional)</label>
                            <input type="text" name="event_token" class="form-control">
                            <small class="text-muted">Unique identifier for the event</small>
                        </div>
                        <button type="submit" name="add_event" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i> Add Event
                        </button>
                    </form>
                    
                    <!-- Current Events -->
                    <h6 class="mb-3">Current Events</h6>
                    <div class="list-group">
                        <?php 
                        $events = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
                        while($event = $events->fetch_assoc()): 
                        ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($event['name']); ?></strong>
                                    <?php if($event['token']): ?>
                                        <small class="text-muted d-block">Token: <?php echo $event['token']; ?></small>
                                    <?php endif; ?>
                                    <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($event['created_at'])); ?></small>
                                </div>
                                <?php if($event['id'] > 1): ?>
                                    <form action="actions/manage_events.php" method="post" class="d-inline">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="delete_event" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Delete this event?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Create floating particles
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        const colors = [
            'rgba(92, 130, 253, 0.7)',   // Blue
            'rgba(255, 107, 157, 0.7)',  // Pink
            'rgba(120, 255, 120, 0.7)',  // Green
            'rgba(255, 255, 120, 0.7)',  // Yellow
            'rgba(180, 120, 255, 0.7)'   // Purple
        ];
        
        for(let i = 0; i < 25; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + 'vw';
            
            // Random size: 8px to 25px
            const size = Math.random() * 17 + 8;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            
            // Random color
            particle.style.background = colors[Math.floor(Math.random() * colors.length)];
            
            particle.style.animationDelay = Math.random() * 20 + 's';
            particle.style.opacity = Math.random() * 0.5 + 0.3;
            
            // Make some particles move faster
            const duration = Math.random() * 10 + 15;
            particle.style.animationDuration = duration + 's';
            
            particlesContainer.appendChild(particle);
        }
    }

    // Raffle modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        createParticles();
        
        // SweetAlert2 Messages
        <?php if(isset($_SESSION['draw_error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Draw Error',
            text: '<?php echo addslashes($_SESSION['draw_error']); ?>',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.5)'
        });
        <?php 
            unset($_SESSION['draw_error']);
        endif; 
        ?>
        
        <?php if(isset($_SESSION['message'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['message_type'] == 'error' ? 'error' : 'success'; ?>',
            title: '<?php echo $_SESSION['message_type'] == 'error' ? 'Error' : 'Success'; ?>',
            text: '<?php echo addslashes($_SESSION['message']); ?>',
            confirmButtonColor: '<?php echo $_SESSION['message_type'] == 'error' ? '#d33' : '#3085d6'; ?>',
            confirmButtonText: 'OK',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.5)'
        });
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif; 
        ?>
        
<?php if(isset($_SESSION['upload_result'])): 
    $result = $_SESSION['upload_result'];
    $added = $result['added'];
    $skipped = $result['skipped'];
    $invalid = isset($result['invalid']) ? $result['invalid'] : 0;
    $total = $result['total'];
    $processed = isset($result['processed']) ? $result['processed'] : $total;
?>
Swal.fire({
    icon: '<?php echo $added > 0 ? "success" : "info"; ?>',
    title: '<?php echo $added > 0 ? "Upload Complete" : "Upload Results"; ?>',
    html: 'Total rows in file: <b><?php echo $total; ?></b><br>' +
          'Valid rows processed: <b><?php echo $processed; ?></b><br>' +
          'New participants added: <b><?php echo $added; ?></b><br>' +
          'Duplicates skipped: <b><?php echo $skipped; ?></b><br>' +
          <?php if($invalid > 0): ?>
          'Invalid names skipped: <b class="text-danger"><?php echo $invalid; ?></b><br>' +
          'Note: Names must contain only letters, spaces, apostrophes, dots, and hyphens.' +
          <?php endif; ?>
          '',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK',
    background: 'rgba(255, 255, 255, 0.95)',
    backdrop: 'rgba(0, 0, 0, 0.5)'
});
<?php 
    unset($_SESSION['upload_result']);
endif; 
?>
        
        <?php if(isset($_SESSION['upload_error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Upload Error',
            text: '<?php echo addslashes($_SESSION['upload_error']); ?>',
            confirmButtonColor: '#d33',
            confirmButtonText: 'OK',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.5)'
        });
        <?php 
            unset($_SESSION['upload_error']);
        endif; 
        ?>
        
        const winnerCountInput = document.getElementById('winnerCountInput');
        const decreaseBtn = document.getElementById('decreaseBtn');
        const increaseBtn = document.getElementById('increaseBtn');
        const quickButtons = document.querySelectorAll('.winner-quick-btn');
        const activeCount = document.getElementById('activeCount');
        const maxWinners = document.getElementById('maxWinners');
        const drawEventSelect = document.getElementById('drawEventSelect');
        const startDrawBtn = document.getElementById('startDrawBtn');
        
        // Function to update participant count based on selected event
        function updateParticipantCount() {
            const eventId = drawEventSelect ? drawEventSelect.value : <?php echo $current_event_id; ?>;
            
            // Fetch participant count for selected event
            fetch(`get_participant_count.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const count = data.count;
                        const currentMax = Math.min(50, count);
                        
                        // Update display
                        if(activeCount) activeCount.textContent = count;
                        if(maxWinners) maxWinners.textContent = currentMax;
                        
                        // Update input max
                        if(winnerCountInput) {
                            winnerCountInput.max = currentMax;
                            
                            // Adjust current value if it exceeds max
                            if(parseInt(winnerCountInput.value) > currentMax) {
                                winnerCountInput.value = currentMax;
                            }
                        }
                        
                        // Update button states
                        updateWinnerOptions();
                        
                        // Enable/disable start button
                        if(startDrawBtn) {
                            startDrawBtn.disabled = count === 0;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching participant count:', error);
                });
        }
        
        if(drawEventSelect) {
            drawEventSelect.addEventListener('change', updateParticipantCount);
        }
        
        if(winnerCountInput && activeCount) {
            const maxParticipants = parseInt(activeCount.textContent);
            const currentMax = Math.min(50, maxParticipants);
            
            winnerCountInput.max = currentMax;
            
            // Update quick button states
            function updateWinnerOptions() {
                const currentValue = parseInt(winnerCountInput.value);
                const currentMax = parseInt(winnerCountInput.max);
                
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
                if(val < parseInt(winnerCountInput.max)) winnerCountInput.value = val + 1;
                updateWinnerOptions();
            });
            
            quickButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    const currentMax = parseInt(winnerCountInput.max);
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
                const eventSelect = this.querySelector('#drawEventSelect');
                const eventName = eventSelect.options[eventSelect.selectedIndex].text;
                const activeCount = parseInt(document.getElementById('activeCount').textContent);
                
                if(activeCount === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Participants',
                        text: 'Please add participants to this event before drawing!',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Draw',
                    html: `Draw <b>${winnerCount}</b> winner(s) for<br><b>"${prize}"</b><br>in event <b>"${eventName}"</b>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, draw now!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }
        
        // Add some fun animations
        const cards = document.querySelectorAll('.glass-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Filter functionality for winners history
        const filterDate = document.getElementById('filterDate');
        const filterCategory = document.getElementById('filterCategory');
        const filterEvent = document.getElementById('filterEvent');
        const tableRows = document.querySelectorAll('#winnersTable tbody tr');
        
        function filterWinners() {
            const dateVal = filterDate.value;
            const categoryVal = filterCategory.value;
            const eventVal = filterEvent.value;
            
            tableRows.forEach(row => {
                const rowDate = row.getAttribute('data-date');
                const rowCategory = row.getAttribute('data-category');
                const rowEvent = row.getAttribute('data-event');
                
                const dateMatch = !dateVal || rowDate === dateVal;
                const categoryMatch = !categoryVal || rowCategory === categoryVal;
                const eventMatch = !eventVal || rowEvent === eventVal;
                
                row.style.display = (dateMatch && categoryMatch && eventMatch) ? '' : 'none';
            });
        }
        
        if(filterDate) filterDate.addEventListener('change', filterWinners);
        if(filterCategory) filterCategory.addEventListener('change', filterWinners);
        if(filterEvent) filterEvent.addEventListener('change', filterWinners);
        
        // Initialize Winners History Modal Search
        const winnersHistoryModal = document.getElementById('winnersHistoryModal');
        if(winnersHistoryModal) {
            winnersHistoryModal.addEventListener('shown.bs.modal', function() {
                // Reset filters when modal opens
                if(filterDate) filterDate.value = '';
                if(filterCategory) filterCategory.value = '';
                if(filterEvent) filterEvent.value = '<?php echo $current_event_id; ?>';
                tableRows.forEach(row => row.style.display = '');
            });
        }
    });

// Add loading state to draw raffle button - With CSS Loader
function showDrawLoading() {
    const drawBtn = document.getElementById('startDrawBtn');
    if(drawBtn) {
        drawBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Drawing...';
        drawBtn.disabled = true;
        
        // Get the modal
        const modal = document.getElementById('raffleModal');
        const modalContent = modal.querySelector('.modal-content');
        
        // Create loading overlay with CSS loader
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loader-overlay';
        
        // Choose one of these loaders (uncomment the one you prefer):
        
        // 1. Pulse Loader (Recommended)
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="css-loader"></div>
                <p class="loading-text mt-4">Drawing winners<span></span></p>
            </div>
        `;
        
        /*
        // 2. Ring Loader
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="ring-loader"></div>
                <p class="loading-text mt-4">Drawing winners<span></span></p>
            </div>
        `;
        
        // 3. Dots Loader
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="dots-loader"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                <p class="loading-text mt-4">Drawing winners<span></span></p>
            </div>
        `;
        
        // 4. Wave Loader
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="wave-loader">
                    <div></div><div></div><div></div>
                </div>
                <p class="loading-text mt-4">Drawing winners<span></span></p>
            </div>
        `;
        */
        
        // Append to modal content (relative positioning)
        modalContent.style.position = 'relative';
        modalContent.appendChild(loadingOverlay);
        
        // Also add a global overlay to prevent clicking elsewhere
        const globalOverlay = document.createElement('div');
        globalOverlay.className = 'modal-backdrop fade show';
        globalOverlay.style.zIndex = '1050';
        document.body.appendChild(globalOverlay);
    }
}

// Function to remove loading overlay
function removeDrawLoading() {
    const loadingOverlay = document.querySelector('#raffleModal .loader-overlay');
    if(loadingOverlay) {
        loadingOverlay.remove();
    }
    
    // Remove global overlay
    const globalOverlays = document.querySelectorAll('.modal-backdrop');
    globalOverlays.forEach(overlay => overlay.remove());
    
    // Reset button
    const drawBtn = document.getElementById('startDrawBtn');
    if(drawBtn) {
        drawBtn.innerHTML = '<i class="fas fa-play me-2"></i> Start Draw';
        drawBtn.disabled = false;
    }
}

// Update the form submission handler to show loading
const raffleForm = document.querySelector('form[action="actions/draw_raffle.php"]');
if(raffleForm) {
    raffleForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Always prevent default first
        
        const prize = this.querySelector('input[name="prize"]').value;
        const winnerCount = this.querySelector('#winnerCountInput').value;
        const eventSelect = this.querySelector('#drawEventSelect');
        const eventName = eventSelect.options[eventSelect.selectedIndex].text;
        const activeCount = parseInt(document.getElementById('activeCount').textContent);
        
        if(activeCount === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Participants',
                text: 'Please add participants to this event before drawing!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Draw',
            html: `Draw <b>${winnerCount}</b> winner(s) for<br><b>"${prize}"</b><br>in event <b>"${eventName}"</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, draw now!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading animation
                showDrawLoading();
                
                // Submit the form after a small delay to ensure spinner shows
                setTimeout(() => {
                    const form = e.target;
                    // Store the form data
                    const formData = new FormData(form);
                    
                    // Submit via fetch to handle loading state better
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if(response.ok) {
                            // Remove loading before redirect
                            removeDrawLoading();
                            window.location.href = response.url || '../index.php?event=' + formData.get('event_id');
                        } else {
                            // Hide loading on error
                            removeDrawLoading();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred during the draw. Please try again.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    }).catch(error => {
                        // Hide loading on error
                        removeDrawLoading();
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred during the draw. Please try again.',
                            confirmButtonColor: '#3085d6'
                        });
                    });
                }, 100);
            }
        });
    });
}

    // Winners History Functionality
let currentPage = 1;
let itemsPerPage = 25;
let totalWinners = 0;
let filteredWinners = [];
let allWinners = [];

// Load winners data when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const winnersHistoryModal = document.getElementById('winnersHistoryModal');
    if(winnersHistoryModal) {
        winnersHistoryModal.addEventListener('shown.bs.modal', function() {
            loadWinnersData();
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchWinners');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            currentPage = 1;
            applyFilters();
        });
    }
    
    if(clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            currentPage = 1;
            applyFilters();
        });
    }
    
    // Filter event listeners
    const filterDate = document.getElementById('filterDate');
    const filterCategory = document.getElementById('filterCategory');
    const filterEvent = document.getElementById('filterEvent');
    const resultsPerPage = document.getElementById('resultsPerPage');
    
    [filterDate, filterCategory, filterEvent, resultsPerPage].forEach(element => {
        if(element) {
            element.addEventListener('change', function() {
                if(element === resultsPerPage) {
                    itemsPerPage = parseInt(this.value);
                }
                currentPage = 1;
                applyFilters();
            });
        }
    });
    
    // Pagination buttons
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    
    if(prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if(currentPage > 1) {
                currentPage--;
                displayWinners();
            }
        });
    }
    
    if(nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            const totalPages = Math.ceil(filteredWinners.length / itemsPerPage);
            if(currentPage < totalPages) {
                currentPage++;
                displayWinners();
            }
        });
    }
});

// Load winners data from server
function loadWinnersData() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const tableBody = document.getElementById('winnersTableBody');
    const noResults = document.getElementById('noResults');
    
    // Show loading
    loadingIndicator.style.display = 'block';
    tableBody.innerHTML = '';
    noResults.style.display = 'none';
    
    // Fetch data via AJAX
    fetch('actions/get_winners_data.php')
        .then(response => response.json())
        .then(data => {
            allWinners = data.winners || [];
            totalWinners = data.total || 0;
            
            // Update total count
            document.getElementById('totalWinners').textContent = totalWinners;
            
            // Apply initial filters
            applyFilters();
            
            // Hide loading
            loadingIndicator.style.display = 'none';
        })
        .catch(error => {
            console.error('Error loading winners:', error);
            loadingIndicator.style.display = 'none';
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading winners data
                    </td>
                </tr>
            `;
        });
}

// Apply all filters
function applyFilters() {
    const searchTerm = document.getElementById('searchWinners')?.value.toLowerCase() || '';
    const filterDate = document.getElementById('filterDate')?.value || '';
    const filterCategory = document.getElementById('filterCategory')?.value || '';
    const filterEvent = document.getElementById('filterEvent')?.value || '';
    
    filteredWinners = allWinners.filter(winner => {
        // Search filter
        const matchesSearch = !searchTerm || 
            winner.fullname.toLowerCase().includes(searchTerm) ||
            winner.prize.toLowerCase().includes(searchTerm);
        
        // Date filter
        const matchesDate = !filterDate || winner.win_date.startsWith(filterDate);
        
        // Category filter
        const matchesCategory = !filterCategory || winner.prize_category_id == filterCategory;
        
        // Event filter
        const matchesEvent = !filterEvent || winner.event_id == filterEvent;
        
        return matchesSearch && matchesDate && matchesCategory && matchesEvent;
    });
    
    // Display results
    displayWinners();
}

// Display winners in table with pagination
function displayWinners() {
    const tableBody = document.getElementById('winnersTableBody');
    const noResults = document.getElementById('noResults');
    const resultsInfo = document.getElementById('resultsInfo');
    const pageInfo = document.getElementById('pageInfo');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    
    // Calculate pagination
    const totalItems = filteredWinners.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    const pageWinners = filteredWinners.slice(startIndex, endIndex);
    
    // Update info
    resultsInfo.textContent = `Showing ${startIndex + 1}-${endIndex} of ${totalItems} winners`;
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    
    // Update pagination buttons
    prevPageBtn.disabled = currentPage <= 1;
    nextPageBtn.disabled = currentPage >= totalPages;
    
    // Clear table
    tableBody.innerHTML = '';
    
    if (pageWinners.length === 0) {
        noResults.style.display = 'block';
        return;
    }
    
    noResults.style.display = 'none';
    
    // Populate table
    pageWinners.forEach((winner, index) => {
        const winDate = new Date(winner.win_date);
        const dateFormatted = winDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        const timeFormatted = winDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${startIndex + index + 1}</td>
            <td><strong>${winner.fullname}</strong></td>
            <td>
                <span class="badge bg-success" style="background: ${winner.color || '#198754'} !important">
                    ${winner.prize}
                </span>
            </td>
            <td>
                ${winner.category_name ? `
                    <span class="badge bg-secondary">
                        ${winner.category_name}
                    </span>
                ` : ''}
            </td>
            <td>
                <small>${winner.event_name || 'N/A'}</small>
            </td>
            <td>
                <div>
                    <div>${dateFormatted}</div>
                    <div class="text-muted small">${timeFormatted}</div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Report Generation Functions
function generateReport(format) {
    // Set default format in form
    const reportForm = document.getElementById('reportForm');
    const formatSelect = reportForm.querySelector('select[name="format"]');
    formatSelect.value = format;
    
    // Set event from current filter
    const filterEvent = document.getElementById('filterEvent').value;
    const reportEventSelect = document.getElementById('reportEventSelect');
    if (filterEvent) {
        reportEventSelect.value = filterEvent;
    }
    
    // Show report modal
    const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
    reportModal.show();
}

function submitReportForm() {
    const form = document.getElementById('reportForm');
    
    // Add current search/filter parameters to the form
    const searchInput = document.createElement('input');
    searchInput.type = 'hidden';
    searchInput.name = 'search';
    searchInput.value = document.getElementById('searchWinners').value;
    form.appendChild(searchInput);
    
    const filterDate = document.createElement('input');
    filterDate.type = 'hidden';
    filterDate.name = 'filter_date';
    filterDate.value = document.getElementById('filterDate').value;
    form.appendChild(filterDate);
    
    const filterCategory = document.createElement('input');
    filterCategory.type = 'hidden';
    filterCategory.name = 'filter_category';
    filterCategory.value = document.getElementById('filterCategory').value;
    form.appendChild(filterCategory);
    
    const filterEvent = document.createElement('input');
    filterEvent.type = 'hidden';
    filterEvent.name = 'filter_event';
    filterEvent.value = document.getElementById('filterEvent').value;
    form.appendChild(filterEvent);
    
    // Submit form
    form.submit();
    
    // Close modal
    const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
    reportModal.hide();
}
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

    // Drag and Drop File Upload
document.addEventListener('DOMContentLoaded', function() {
    const fileDropArea = document.getElementById('fileDropArea');
    const fileInput = document.getElementById('participantsFile');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = uploadProgress.querySelector('.progress-bar');
    const bulkUploadForm = document.getElementById('bulkUploadForm');
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Highlight drop area when dragging over
    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        fileDropArea.classList.add('dragover');
    }
    
    function unhighlight(e) {
        fileDropArea.classList.remove('dragover');
    }
    
    // Handle dropped files
    fileDropArea.addEventListener('drop', handleDrop, false);
    fileDropArea.addEventListener('click', () => fileInput.click());
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            fileInput.files = files;
            handleFiles(files);
        }
    }
    
    // Handle file selection via click
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        const file = files[0];
        
        // Validate file type
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 
                              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        const allowedExtensions = ['.csv', '.xls', '.xlsx'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(fileExt)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please upload only CSV (.csv), Excel 97-2003 (.xls), or Excel (.xlsx) files.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Validate file size (max 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'File size should not exceed 5MB.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Update preview
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        filePreview.style.display = 'block';
        uploadBtn.disabled = false;
        
        // Preview file content (first few lines)
        previewFileContent(file);
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function previewFileContent(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const content = e.target.result;
            let lines = [];
            
            if (file.name.toLowerCase().endsWith('.csv')) {
                lines = content.split('\n').slice(0, 5); // Show first 5 lines
            } else {
                // For Excel files, we can't easily preview without parsing
                lines = ['Excel file content preview not available.'];
            }
            
            // Add preview to modal or console
            console.log('File preview:', lines);
        };
        
        if (file.name.toLowerCase().endsWith('.csv')) {
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    }
    
    // Remove file
    removeFileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.value = '';
        filePreview.style.display = 'none';
        uploadBtn.disabled = true;
    });
    
    // Form submission with progress simulation
    bulkUploadForm.addEventListener('submit', function(e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'No File Selected',
                text: 'Please select a file to upload.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Show progress bar
        uploadProgress.style.display = 'block';
        progressBar.style.width = '0%';
        
        // Simulate upload progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 10;
            progressBar.style.width = progress + '%';
            
            if (progress >= 90) {
                clearInterval(progressInterval);
            }
        }, 200);
        
        // The actual upload will be handled by PHP
        // When form submits, the page will reload and show results via SweetAlert
    });
    
    // Add validation to single participant form
    const singleForm = document.querySelector('#single form');
    if (singleForm) {
        const nameInput = singleForm.querySelector('input[name="fullname"]');
        const eventSelect = singleForm.querySelector('select[name="event_id"]');
        
        nameInput.addEventListener('input', function() {
            validateParticipantName(this);
        });
        
        nameInput.addEventListener('blur', function() {
            validateParticipantName(this);
        });
        
        singleForm.addEventListener('submit', function(e) {
            if (!validateParticipantName(nameInput)) {
                e.preventDefault();
                return;
            }
            
            // Check for duplicate active participant
            checkDuplicateParticipant(nameInput.value, eventSelect.value, e);
        });
    }
});

function validateParticipantName(input) {
    const value = input.value.trim();
    const namePattern = /^[A-Za-zÀ-ÿ\s'.-]+$/; // Allows letters, spaces, apostrophes, dots, and hyphens
    const feedbackElement = input.nextElementSibling || createFeedbackElement(input);
    
    // Clear previous validation
    input.classList.remove('is-invalid', 'is-valid');
    feedbackElement.classList.remove('show');
    feedbackElement.textContent = '';
    
    // Check if empty
    if (value === '') {
        input.classList.add('is-invalid');
        feedbackElement.textContent = 'Please enter a participant name.';
        feedbackElement.classList.add('show');
        return false;
    }
    
    // Check for invalid characters
    if (!namePattern.test(value)) {
        input.classList.add('is-invalid');
        feedbackElement.textContent = 'Name can only contain letters, spaces, apostrophes, dots, and hyphens.';
        feedbackElement.classList.add('show');
        return false;
    }
    
    // Check length (reasonable limits)
    if (value.length < 2) {
        input.classList.add('is-invalid');
        feedbackElement.textContent = 'Name must be at least 2 characters long.';
        feedbackElement.classList.add('show');
        return false;
    }
    
    if (value.length > 100) {
        input.classList.add('is-invalid');
        feedbackElement.textContent = 'Name must not exceed 100 characters.';
        feedbackElement.classList.add('show');
        return false;
    }
    
    // Valid
    input.classList.add('is-valid');
    return true;
}

function createFeedbackElement(input) {
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    input.parentNode.insertBefore(feedback, input.nextSibling);
    return feedback;
}

function checkDuplicateParticipant(name, eventId, submitEvent) {
    // Show loading state
    const submitBtn = submitEvent.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
    submitBtn.disabled = true;
    
    fetch(`actions/check_participant.php?name=${encodeURIComponent(name)}&event_id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                // Duplicate found, prevent submission
                submitEvent.preventDefault();
                const nameInput = submitEvent.target.querySelector('input[name="fullname"]');
                nameInput.classList.add('is-invalid');
                nameInput.classList.remove('is-valid');
                
                const feedbackElement = nameInput.nextElementSibling || createFeedbackElement(nameInput);
                feedbackElement.textContent = data.message;
                feedbackElement.classList.add('show');
                
                // Focus on the input
                nameInput.focus();
                nameInput.select();
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplicate Participant',
                    text: data.message,
                    confirmButtonColor: '#3085d6'
                });
            } else {
                // No duplicate, allow submission
                submitEvent.target.submit();
            }
        })
        .catch(error => {
            console.error('Error checking duplicate:', error);
            // Allow submission if check fails
            submitEvent.target.submit();
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}
// Simple Sound Manager
class SimpleSoundManager {
    constructor() {
        this.audio = document.getElementById('winnerSound');
        this.enabled = true;
        this.init();
    }
    
    init() {
        // Load preference from localStorage
        const savedEnabled = localStorage.getItem('raffleSoundEnabled');
        if (savedEnabled !== null) {
            this.enabled = savedEnabled === 'true';
        }
        this.updateUI();
        
        // Fix for mobile browsers
        this.setupMobileAudio();
    }
    
    setupMobileAudio() {
        // Mobile browsers require user interaction before playing audio
        document.addEventListener('click', () => {
            if (this.audio) {
                this.audio.load(); // Preload on first interaction
            }
        }, { once: true });
    }
    
    play() {
        if (!this.enabled || !this.audio) return;
        
        try {
            // Reset audio to start
            this.audio.currentTime = 0;
            
            // Play the sound
            const playPromise = this.audio.play();
            
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.log('Audio playback failed:', error);
                    // Auto-resume on next user interaction
                    document.addEventListener('click', () => {
                        this.audio.play().catch(e => console.log('Retry failed:', e));
                    }, { once: true });
                });
            }
        } catch (error) {
            console.error('Error playing sound:', error);
        }
    }
    
    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('raffleSoundEnabled', this.enabled.toString());
        this.updateUI();
        
        // Play test sound when enabling
        if (this.enabled) {
            this.play();
        }
    }
    
    updateUI() {
        const toggleBtn = document.getElementById('soundToggleBtn');
        if (!toggleBtn) return;
        
        const icon = toggleBtn.querySelector('i');
        if (this.enabled) {
            icon.className = 'fas fa-volume-up';
            toggleBtn.classList.remove('muted');
            toggleBtn.title = 'Mute Sound';
        } else {
            icon.className = 'fas fa-volume-mute';
            toggleBtn.classList.add('muted');
            toggleBtn.title = 'Unmute Sound';
        }
    }
}

// Initialize sound manager
let soundManager;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sound manager
    soundManager = new SimpleSoundManager();
    
    // Set up toggle button
    const soundToggleBtn = document.getElementById('soundToggleBtn');
    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', function() {
            soundManager.toggle();
        });
    }
    
    // Play sound when winner modal appears
    const winnerModal = document.getElementById('winnerPopup');
    if (winnerModal) {
        // Play sound when modal is shown
        setTimeout(() => {
            if (soundManager) {
                soundManager.play();
            }
        }, 500); // 0.5 second delay for dramatic effect
    }
});
    </script>
</body>
</html>