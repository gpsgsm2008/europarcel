$(document).ready(function () {
    // Add CSS for locker section
    const style = document.createElement('style');
    style.textContent = `
        .europarcel-locker-section {
            display: none;
            margin-top: 10px;
            margin-left: 25px;
            padding: 10px;
            border-left: 3px solid #0d6efd;
            background-color: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        
        .europarcel-locker-section.visible {
            display: block !important;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .europarcel-locker-info {
            margin-top: 10px;
            display: none;
        }
        
        .europarcel-locker-info.visible {
            display: block;
        }
    `;
    document.head.appendChild(style);

    // Initialize locker functionality
    initEuroparcelLocker();
    
    // Listen for shipping modal open
    $(document).on('shown.bs.modal', '#modal-shipping', function () {
        setTimeout(initEuroparcelLocker, 300);
    });
    
    // Listen for click on shipping methods button
    $('#button-shipping-methods').on('click', function () {
        setTimeout(initEuroparcelLocker, 500);
    });
});

/**
 * Initialize Europarcel locker functionality
 */
function initEuroparcelLocker() {
    const modalBody = $('#modal-shipping .modal-body');
    if (!modalBody.length) return;
    
    // Find all shipping methods
    const shippingMethods = modalBody.find('.form-check');
    
    shippingMethods.each(function () {
        const formCheck = $(this);
        const radio = formCheck.find('input[name="shipping_method"]');
        const methodCode = radio.val();
        
        // Check if this is the Europarcel Locker method
        if (methodCode === 'europarcel.locker') {
            // Check if locker section already exists
            if (!formCheck.find('.europarcel-locker-section').length) {
                // Add locker section
                const lockerHtml = createLockerSection();
                formCheck.append(lockerHtml);
                
                // Initialize buttons for this section
                initLockerButtons(formCheck);

                // Fetch saved locker from session (if any)
                fetchSavedLocker(formCheck.find('.europarcel-locker-section'));
            }
            
            // Show/hide based on selection
            const lockerSection = formCheck.find('.europarcel-locker-section');
            if (radio.is(':checked')) {
                lockerSection.addClass('visible');
            } else {
                lockerSection.removeClass('visible');
            }
        } else {
            // Remove locker section if it exists
            formCheck.find('.europarcel-locker-section').remove();
        }
    });
    
    // Listen for shipping method change
    modalBody.off('change', 'input[name="shipping_method"]').on('change', 'input[name="shipping_method"]', function () {
        const radio = $(this);
        const lockerSection = radio.closest('.form-check').find('.europarcel-locker-section');
        
        // Hide all locker sections
        modalBody.find('.europarcel-locker-section').removeClass('visible');
        
        // Show only for selected method
        if (radio.val() === 'europarcel.locker') {
            lockerSection.addClass('visible');
        }
    });
}

/**
 * Create HTML for locker section
 */
function createLockerSection() {
    var cfg = (typeof europarcelConfig !== 'undefined') ? europarcelConfig : {};
    var btnChoose = cfg.button_choose_locker || 'Choose Locker';
    var btnChange = cfg.button_change_locker || 'Change locker';
    var txtSelected = cfg.text_locker_selected || 'Selected locker:';

    return `
        <div class="europarcel-locker-section">
            <button type="button" class="btn btn-sm btn-primary europarcel-choose-locker mb-2">
                <i class="fa fa-map-marker me-1"></i>${btnChoose}
            </button>
            
            <div class="europarcel-locker-info">
                <div class="alert alert-success alert-sm mb-0">
                    <p class="mb-1"><strong>${txtSelected}</strong> <span class="europarcel-locker-name"></span></p>
                    <p class="mb-1 small europarcel-locker-address"></p>
                    <button type="button" class="btn btn-sm btn-outline-primary europarcel-change-locker mt-1">
                        ${btnChange}
                    </button>
                    <input type="hidden" class="europarcel-locker-data" value="">
                </div>
            </div>
        </div>
    `;
}

/**
 * Initialize locker buttons for a section
 */
function initLockerButtons(container) {
    // "Choose Locker" button
    container.find('.europarcel-choose-locker').off('click').on('click', function () {
        openLockerModal(this);
    });
    
    // "Change locker" button
    container.find('.europarcel-change-locker').off('click').on('click', function () {
        openLockerModal(this);
    });
}

/**
 * Opens the modal window for locker selection
 */
function openLockerModal(button) {
    const btn = $(button);
    const container = btn.closest('.europarcel-locker-section');
    
    // Get address data from checkout form
    const addressData = getCustomerAddress();
    
    // Courier IDs (configured in admin)
    const courierIds = getEuroparcelCourierIds();
    
    // Build the iframe URL
    const iframeUrl = buildLockerIframeUrl(addressData, courierIds);
    
    // Show the modal
    if (typeof EuroparcelModal !== 'undefined') {
        EuroparcelModal.show(iframeUrl);
    } else {
        console.error('EuroparcelModal is not available');
        // Fallback - open in new window
        window.open(iframeUrl, '_blank', 'width=1200,height=800');
    }
}

/**
 * Get customer address from checkout form
 */
function getCustomerAddress() {
    // Try to get address from checkout form
    const addressData = {
        country_code: 'RO',
        locality_name: '',
        county_name: ''
    };
    
    // Logic to get address from OpenCart checkout form:
    try {
        // Check if address fields exist
        const shippingAddress = $('input[name="shipping_address"]').val() || '';
        const shippingCity = $('input[name="shipping_city"]').val() || '';
        const shippingZone = $('select[name="shipping_zone_id"] option:selected').text() || '';
        
        if (shippingCity) addressData.locality_name = shippingCity;
        if (shippingZone) addressData.county_name = shippingZone;
        
    } catch (e) {
        console.log('Could not get customer address:', e);
    }
    
    return addressData;
}

/**
 * Get Europarcel courier IDs from configuration
 */
function getEuroparcelCourierIds() {
    // Return configured courier IDs
    if (typeof europarcelConfig !== 'undefined' && europarcelConfig.carrier_ids) {
        return europarcelConfig.carrier_ids;
    }
}

/**
 * Builds the iframe URL with necessary parameters
 */
function buildLockerIframeUrl(addressData, courierIds) {
    const baseUrl = 'https://maps.europarcel.com';
    const params = new URLSearchParams();
    
    // Add address parameters
    params.append('country_code', addressData.country_code || 'RO');
    if (addressData.locality_name) {
        params.append('locality_name', addressData.locality_name);
    }
    if (addressData.county_name) {
        params.append('county_name', addressData.county_name);
    }
    
    // Add courier IDs
    if (courierIds) {
        params.append('carrier_id', courierIds);
    }
    
    // Add callback parameter (required)
    params.append('callback', 'parent');
    
    return baseUrl + '?' + params.toString();
}

/**
 * Handles messages received from the locker iframe
 */
window.addEventListener('message', function(event) {
    // Validate origin for security
    if (event.origin !== 'https://maps.europarcel.com') {
        return;
    }
    
    const data = event.data;
    
    // Check if this is a locker selection message
    if (data.type === 'locker-selected') {
        handleLockerSelection(data.locker);
    }
}, false);

/**
 * Handles locker selection from iframe
 */
function handleLockerSelection(lockerData) {
    // Find active locker section (for selected shipping method)
    const activeLockerSection = $('.europarcel-locker-section.visible').first();
    if (!activeLockerSection.length) return;
    
    // Save locker data
    saveLockerToSession(lockerData);
    
    // Update the display
    updateLockerDisplay(activeLockerSection, lockerData);
    
    // Close the modal
    if (typeof EuroparcelModal !== 'undefined') {
        EuroparcelModal.close();
    }
}

/**
 * Saves the locker to session via AJAX
 */
function saveLockerToSession(lockerData) {
    $.ajax({
        url: 'index.php?route=extension/europarcel/europarcel/checkout.saveLocker',
        type: 'POST',
        timeout: 10000,
        data: {
            locker_data: lockerData
        },
        success: function(response) {
            if (response.success) {
                console.log('Locker saved successfully');
            } else {
                console.error('Error saving locker:', response.error);
                alert('Could not save locker selection. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error saving locker:', error);
            alert('Could not save locker selection. Please try again.');
        }
    });
}

/**
 * Updates the selected locker display
 */
function updateLockerDisplay(container, lockerData) {
    // Hide the "Choose Locker" button
    container.find('.europarcel-choose-locker').hide();
    
    // Update locker information
    container.find('.europarcel-locker-name').text(lockerData.name || 'Locker Europarcel');
    container.find('.europarcel-locker-address').text(lockerData.address || '');
    container.find('.europarcel-locker-data').val(JSON.stringify(lockerData));
    
    // Show locker information
    container.find('.europarcel-locker-info').addClass('visible').show();
}

/**
 * Fetches the previously saved locker from the PHP session and displays it
 */
function fetchSavedLocker(container) {
    $.ajax({
        url: 'index.php?route=extension/europarcel/europarcel/checkout.getLocker',
        type: 'GET',
        timeout: 10000,
        dataType: 'json',
        success: function(response) {
            if (response.success && response.locker) {
                updateLockerDisplay(container, response.locker);
            }
        },
        error: function(xhr, status, error) {
            console.log('Could not fetch saved locker:', error);
        }
    });
}

/**
 * Toggle Europarcel locker section visibility
 */
function toggleEuroparcelLocker() {
    const selectedRadio = $('input[name="shipping_method"]:checked');
    const isEuroparcelLocker = selectedRadio.val() === 'europarcel.locker';
    
    // Hide all locker sections
    $('.europarcel-locker-section').removeClass('visible').hide();
    
    // Show only the section for selected method
    if (isEuroparcelLocker && selectedRadio.length) {
        const lockerSection = selectedRadio.closest('.form-check').find('.europarcel-locker-section');
        lockerSection.addClass('visible').show();
    }
}