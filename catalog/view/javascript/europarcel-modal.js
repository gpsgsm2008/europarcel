/**
 * EuroParcel Modal functionality for OpenCart
 */
(function ($) {
    'use strict';

    window.EuroparcelModal = {
        /**
         * Show the locker selection modal
         */
        show: function (iframeUrl) {
            var isMobile = window.innerWidth <= 768;

            var modalHtml = `
                <div id="europarcel-iframe-modal" style="
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0, 0, 0, 0.5); z-index: 2147483647; display: flex;
                    align-items: ${isMobile ? 'flex-start' : 'center'}; justify-content: center;
                    padding: ${isMobile ? '0' : '20px'};
                ">
                    <div style="
                        background: white; width: 100%; max-width: ${isMobile ? '100%' : '1200px'};
                        height: ${isMobile ? '100%' : '90%'}; border-radius: ${isMobile ? '0' : '8px'};
                        overflow: hidden; position: relative; ${isMobile ? 'margin: 0;' : ''}
                    ">
                        <button id="close-locker-modal" style="
                            position: absolute; top: ${isMobile ? '15px' : '10px'}; right: ${isMobile ? '15px' : '10px'};
                            z-index: 10; background: rgba(0, 0, 0, 0.7); color: white; border: none;
                            border-radius: 50%; width: ${isMobile ? '35px' : '30px'}; height: ${isMobile ? '35px' : '30px'};
                            cursor: pointer; font-size: ${isMobile ? '20px' : '18px'}; line-height: 1;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: all 0.2s ease;
                        "
                        onmouseover="this.style.background='rgba(0,0,0,0.9)'; this.style.transform='scale(1.1)';"
                        onmouseout="this.style.background='rgba(0,0,0,0.7)'; this.style.transform='scale(1)';"
                        >&times;</button>
                        <iframe src="${iframeUrl}" style="width: 100%; height: 100%; border: none;"
                            title="Alege locker Europarcel" id="europarcel-locker-iframe"></iframe>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Prevent scrolling
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';

            if (isMobile) {
                document.body.style.height = '100%';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            }

            // Setup event handlers
            this.setupEventHandlers(isMobile);
        },

        /**
         * Setup modal event handlers
         */
        setupEventHandlers: function (isMobile) {
            // Close button handler
            $('#close-locker-modal').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.EuroparcelModal.close();
            });

            // Click outside to close (desktop only)
            if (!isMobile) {
                $('#europarcel-iframe-modal').on('click', function (e) {
                    if (e.target === this) {
                        window.EuroparcelModal.close();
                    }
                });
            }

            // Escape key handler
            $(document).on('keydown.europarcel', function (e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    window.EuroparcelModal.close();
                }
            });
        },

        /**
         * Close the modal
         */
        close: function () {
            var modal = $('#europarcel-iframe-modal');
            if (!modal.length) {
                return;
            }

            // Restore scrolling
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            document.body.style.height = '';
            document.body.style.position = '';
            document.body.style.width = '';

            // Remove event listeners
            $(document).off('keydown.europarcel');

            // Remove modal from DOM
            modal.remove();
        }
    };
})(jQuery);

