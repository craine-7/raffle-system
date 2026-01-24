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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div id="particles"></div>

    <!-- Settings Button -->
    <button class="btn settings-btn" type="button" data-bs-toggle="modal" data-bs-target="#settingsModal">
        <i class="fas fa-cog"></i>
    </button>

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

        <!-- Error Alert -->
        <?php if(isset($_SESSION['draw_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['draw_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
            unset($_SESSION['draw_error']);
        endif; 
        ?>

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
                    <div class="modal-header border-0 bg-warning">
                        <h2 class="modal-title text-white" id="winnerPopupLabel">
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
                setTimeout(() => {
                    if(document.getElementById('winnerPopup').classList.contains('show')) {
                        closeWinnerPopup();
                    }
                }, 10000);
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
                                <form action="actions/upload_participants.php" method="post" enctype="multipart/form-data">
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
                                        <label class="form-label small">Upload CSV File</label>
                                        <div class="input-group">
                                            <input type="file" name="participants_file" class="form-control" accept=".csv" required>
                                            <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#uploadHelpModal">
                                                <i class="fas fa-question"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Upload CSV file with one name per line</small>
                                    </div>
                                    <button class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload Participants
                                    </button>
                                </form>
                                
                                <!-- Upload Results -->
                                <?php if(isset($_SESSION['upload_result'])): ?>
                                <div class="alert alert-success mt-2 mb-0 p-2">
                                    <small>
                                        Added: <?php echo $_SESSION['upload_result']['added']; ?>, 
                                        Skipped: <?php echo $_SESSION['upload_result']['skipped']; ?>, 
                                        Total: <?php echo $_SESSION['upload_result']['total']; ?>
                                    </small>
                                </div>
                                <?php unset($_SESSION['upload_result']); endif; ?>
                                
                                <?php if(isset($_SESSION['upload_error'])): ?>
                                <div class="alert alert-danger mt-2 mb-0 p-2">
                                    <small><?php echo $_SESSION['upload_error']; ?></small>
                                </div>
                                <?php unset($_SESSION['upload_error']); endif; ?>
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
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Filter by Date</label>
                            <input type="date" class="form-control" id="filterDate">
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                    </div>
                    
                    <!-- Winners Table -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 winners-table" id="winnersTable">
                            <thead>
                                <tr>
                                    <th>Winner</th>
                                    <th>Prize</th>
                                    <th>Category</th>
                                    <th>Event</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_winners = $conn->query("
                                    SELECT w.*, pc.name as category_name, pc.color, e.name as event_name 
                                    FROM winners w
                                    LEFT JOIN prize_categories pc ON w.prize_category_id = pc.id
                                    LEFT JOIN events e ON w.event_id = e.id
                                    ORDER BY w.win_date DESC
                                ");
                                while($winner = $all_winners->fetch_assoc()): 
                                    $win_date = strtotime($winner['win_date']);
                                    $date_formatted = date('M d, Y', $win_date);
                                    $time_formatted = date('h:i A', $win_date);
                                ?>
                                    <tr data-date="<?php echo date('Y-m-d', $win_date); ?>" 
                                        data-category="<?php echo $winner['prize_category_id']; ?>"
                                        data-event="<?php echo $winner['event_id']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($winner['fullname']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success" style="background: <?php echo $winner['color']; ?> !important">
                                                <?php echo htmlspecialchars($winner['prize']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($winner['category_name']): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($winner['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($winner['event_name']); ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <div><?php echo $date_formatted; ?></div>
                                                <div class="text-muted small"><?php echo $time_formatted; ?></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
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
                    alert('Please add participants to this event before drawing!');
                    return;
                }
                
                if(!confirm(`Draw ${winnerCount} winner(s) for "${prize}" in event "${eventName}"?`)) {
                    e.preventDefault();
                }
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