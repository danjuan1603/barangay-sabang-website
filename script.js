document.addEventListener("DOMContentLoaded", function () {
  const toggleButton = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  const navLinks = document.querySelector('.nav-links');
  const servicesDropdownBtn = document.getElementById("servicesDropdownBtn");
  const servicesDropdown = document.getElementById("servicesDropdown");

  // ===== Desktop Dropdown =====
  if (servicesDropdownBtn && servicesDropdown) {
    servicesDropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      const isOpen = servicesDropdown.style.display === "block";
      document.querySelectorAll('.dropdown-content').forEach(d => d.style.display = 'none');
      servicesDropdown.style.display = isOpen ? "none" : "block";
    });

    servicesDropdown.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const target = this.getAttribute("href");
        history.replaceState(null, null, target);
        showServiceSectionByHash();
        servicesDropdown.style.display = "none";
      });
    });
  }

  // ===== Mobile Menu Toggle =====
  if (toggleButton && mobileMenu) {
    toggleButton.addEventListener("click", function () {
      mobileMenu.classList.toggle("show");
      document.querySelectorAll('.dropdown-content').forEach(content => {
        content.style.display = 'none';
      });
    });
  }

  // ===== Clone Nav Links to Mobile Menu =====
  if (navLinks && mobileMenu) {
    mobileMenu.innerHTML = '';
    navLinks.querySelectorAll('a, .dropdown').forEach(item => {
      if (item.classList.contains('dropdown')) {
        const dropdownClone = item.cloneNode(true);
        const dropdownContent = dropdownClone.querySelector('.dropdown-content');

        if (dropdownContent) {
          dropdownContent.style.position = 'static';
          dropdownContent.style.boxShadow = 'none';
          dropdownContent.style.backgroundColor = 'transparent';
          dropdownContent.style.paddingLeft = '15px';
          dropdownContent.style.display = 'none';

          const dropbtn = dropdownClone.querySelector('.dropbtn');
          if (dropbtn) {
            dropbtn.addEventListener('click', function (event) {
              event.preventDefault();
              dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            });
          }

          dropdownContent.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function (event) {
              event.preventDefault();
              const targetId = this.getAttribute('href');
              history.replaceState(null, null, targetId);
              showServiceSectionByHash();
              mobileMenu.classList.remove('show');
              dropdownContent.style.display = 'none';
            });
          });
        }

        mobileMenu.appendChild(dropdownClone);
      } else {
        const clonedLink = item.cloneNode(true);
        clonedLink.addEventListener('click', function (event) {
          event.preventDefault();
          const targetId = this.getAttribute('href');

          if (
            targetId === '#barangay-clearance-section' ||
            targetId === '#incident-reports-section'
          ) {
            history.replaceState(null, null, targetId);
            showServiceSectionByHash();
          } else {
            showAllSections();
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
              targetElement.scrollIntoView({ behavior: 'smooth' });
            }
          }
          mobileMenu.classList.remove('show');
        });
        mobileMenu.appendChild(clonedLink);
      }
    });
  }

  // ===== Close dropdowns if clicked outside =====
  window.addEventListener('click', function (event) {
    if (!event.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-content').forEach(drop => {
        drop.style.display = 'none';
      });
    }
  });

  // ===== Smooth scroll for nav links =====
  document.querySelectorAll('.nav-links a[href^="#"]').forEach(anchor => {
    if (!anchor.classList.contains('dropbtn')) {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (
          targetId === '#barangay-clearance-section' ||
          targetId === '#incident-reports-section'
        ) {
          history.replaceState(null, null, targetId);
          showServiceSectionByHash();
        } else {
          showAllSections();
          const targetElement = document.querySelector(targetId);
          if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth' });
          }
        }
      });
    }
  });

  // ===== Section Display Functions =====
  function hideAllSections() {
    document.querySelectorAll('section:not(#hero):not(#quick-links)').forEach(section => {
      section.style.display = 'none';
    });
  }

  function showSection(sectionId) {
    hideAllSections();
    const targetSection = document.querySelector(sectionId);
    if (targetSection) {
      if (targetSection.closest('#services')) {
        document.getElementById('services').style.display = 'block';
      }
      targetSection.style.display = 'block';
      targetSection.scrollIntoView({ behavior: 'smooth' });
    }
  }

  function showAllSections() {
    document.querySelectorAll('section:not(#hero):not(#quick-links)').forEach(section => {
      section.style.display = 'block';
    });
  }

  // ===== Show Specific Service Section Based on Hash =====
 function showServiceSectionByHash() {
  const hash = window.location.hash;
  const clearance = document.getElementById("barangay-clearance-section");
  const indigency = document.getElementById("certificate-indigency-section");
  const residency = document.getElementById("certificate-residency-section");
  const heading = document.getElementById("certificates-heading");
  const incident = document.getElementById("incident-reports-section");
  const services = document.getElementById("services");

  // Always show the main services container
  if (services) services.style.display = "block";

  // Hide all subsections first
  [clearance, indigency, residency, incident].forEach(el => {
    if (el) el.style.display = "none";
  });
  if (heading) heading.style.display = "none";

  // Show based on hash
  if (hash === "#certificates-group") {
    if (heading) heading.style.display = "block";
    if (clearance) clearance.style.display = "block";
    if (indigency) indigency.style.display = "block";
    if (residency) residency.style.display = "block";
    clearance?.scrollIntoView({ behavior: "smooth" });
  } else if (hash === "#barangay-clearance-section" && clearance) {
    if (heading) heading.style.display = "block";
    clearance.style.display = "block";
    clearance.scrollIntoView({ behavior: "smooth" });
  } else if (hash === "#certificate-indigency-section" && indigency) {
    if (heading) heading.style.display = "block";
    indigency.style.display = "block";
    indigency.scrollIntoView({ behavior: "smooth" });
  } else if (hash === "#certificate-residency-section" && residency) {
    if (heading) heading.style.display = "block";
    residency.style.display = "block";
    residency.scrollIntoView({ behavior: "smooth" });
  } else if (hash === "#incident-reports-section" && incident) {
    incident.style.display = "block";
    incident.scrollIntoView({ behavior: "smooth" });
  }
}
//===========view-all-services============
const viewAllServicesBtn = document.getElementById("view-all-services-btn");
if (viewAllServicesBtn) {
  viewAllServicesBtn.addEventListener("click", function (e) {
    e.preventDefault();

    // Show the full services section
    const services = document.getElementById("services");
    const clearance = document.getElementById("barangay-clearance-section");
    const indigency = document.getElementById("certificate-indigency-section");
    const residency = document.getElementById("certificate-residency-section");
    const incident = document.getElementById("incident-reports-section");
    const heading = document.getElementById("certificates-heading");

    if (services) services.style.display = "block";
    if (heading) heading.style.display = "block";
    if (clearance) clearance.style.display = "block";
    if (indigency) indigency.style.display = "block";
    if (residency) residency.style.display = "block";
    if (incident) incident.style.display = "block";

    services.scrollIntoView({ behavior: "smooth" });
  });
}



  // ===== Hash Change Listener =====
  window.addEventListener("hashchange", showServiceSectionByHash);

  // ===== Manual Hash Link Clicks =====
  document.querySelectorAll('a[href="#barangay-clearance-section"], a[href="#incident-reports-section"]').forEach(link => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = this.getAttribute("href");
      history.replaceState(null, null, target);
      showServiceSectionByHash();
    });
  });

 
  // ===== Initial Service Section Load by Hash =====
  showServiceSectionByHash();

 

  
  const certificateCards = document.querySelectorAll(".certificate-card");
const modal = document.getElementById("confirmation-modal");
const modalTitle = document.getElementById("modal-cert-title");

  // Make certificate cards keyboard-friendly: Enter or Space should activate click
  certificateCards.forEach(card => {
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
  });


});
