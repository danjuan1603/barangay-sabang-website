// Automatic logout when user CLOSES browser/tab
// DISABLED for: page reload, internal navigation

// SOLUTION: Don't logout on beforeunload at all
// Instead, use visibilitychange to detect when tab is truly closing

// Track internal navigation
document.addEventListener('click', function(event) {
  const target = event.target.closest('a');
  if (target && target.href) {
    try {
      const targetUrl = new URL(target.href);
      const currentUrl = new URL(window.location.href);
      
      // If same domain, mark as internal navigation
      if (targetUrl.hostname === currentUrl.hostname) {
        sessionStorage.setItem('internalNav', 'true');
      }
    } catch (e) {}
  }
}, true);   

// Track form submissions
document.addEventListener('submit', function() {
  sessionStorage.setItem('internalNav', 'true');
}, true);

// DISABLED: Auto-logout is too unreliable with page reloads
// The browser cannot reliably distinguish between reload and close
// Recommendation: Use session timeout on server side instead

// If you still want auto-logout, uncomment below (but it will logout on reload):
/*
window.addEventListener('beforeunload', function(event) {
  if (sessionStorage.getItem('internalNav') !== 'true') {
    navigator.sendBeacon('logout.php');
  }
  sessionStorage.removeItem('internalNav');
});
*/
