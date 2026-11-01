<?php
/**
 * payments/donate.php
 * ---------------------------------------------------------------------
 * Public donation page. Displays the giving form. The actual charge
 * happens client-side via Paystack Inline (assets/js/script.js), and
 * the result is verified server-side in payments/verify.php — this
 * page itself does not touch the database.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Give Online';
$token = csrf_token(); // included as a hidden field, checked again in verify.php
require __DIR__ . '/../includes/header.php';
?>

<section class="hero">
    <h1>Give with a Grateful Heart</h1>
    <p>"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." — 2 Corinthians 9:7</p>
</section>

<main class="container">
    <div class="card">
        <h2 style="color:var(--church-blue); margin-top:0;">Donation Form</h2>

        <div id="js-required-notice" style="display:none;" class="alert alert-error">
            JavaScript is required to process payments securely on this page. Please enable it in your browser.
        </div>

        <form id="donation-form" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">

            <div class="form-grid">
                <div class="field full-width">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g. Adaeze Okonkwo" required>
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="080XXXXXXXX" required>
                </div>

                <div class="field">
                    <label for="category">Donation Category</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select a category --</option>
                        <?php foreach (DONATION_CATEGORIES as $cat): ?>
                            <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="amount">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" min="100" step="1" placeholder="5000" required>
                </div>

                <div class="field full-width">
                    <label for="prayer_request">Prayer Request <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                    <textarea id="prayer_request" name="prayer_request" placeholder="Share a prayer request with us, if you wish"></textarea>
                </div>
            </div>

            <button type="submit" class="btn" id="pay-btn">Give Now</button>
        </form>
    </div>
</main>

<!-- Paystack's official inline checkout script -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    // Only the PUBLIC key is ever exposed to the browser — the secret
    // key stays server-side in includes/config.php.
    window.PAYSTACK_PUBLIC_KEY = "<?php echo e(PAYSTACK_PUBLIC_KEY); ?>";
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
