<?php
session_start();
?>
<div class="confirmation-container">
    <style>
        .confirmation-container { display: flex; gap: 30px; padding: 20px; background: #fff; height: 100%; border-radius: 10px; }
        
        /* Left Side: Items */
        .summary-section { flex: 2; border-right: 1px solid #eee; padding-right: 20px; overflow-y: auto; }
        
        /* Right Side: Total & Actions */
        .action-section { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 20px; padding-left: 10px; }
        
        h2 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #34495e; padding-bottom: 10px; }
        
        .summary-item { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .summary-item strong { font-size: 1.1rem; }
        
        .total-box { background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #ddd; text-align: center; }
        .total-label { font-size: 1.2rem; color: #7f8c8d; margin-bottom: 10px; }
        .total-amount { font-size: 2.5rem; color: #2c3e50; font-weight: bold; }
        
        .btn-confirm { background: #27ae60; color: white; border: none; padding: 20px; width: 100%; border-radius: 8px; font-size: 1.2rem; font-weight: bold; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-confirm:hover { background: #219150; transform: translateY(-2px); }
        
        .btn-back { background: transparent; border: 1px solid #aaa; padding: 10px; border-radius: 5px; cursor: pointer; margin-bottom: 20px; display: inline-block; }

        /* Invoice Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: #fff; margin: 2% auto; padding: 40px; border: 1px solid #888; width: 700px; max-height: 90vh; overflow-y: auto; border-radius: 5px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        @media print {
            body * { visibility: hidden; }
            #invoice-print-area, #invoice-print-area * { visibility: visible; }
            #invoice-print-area { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>

    <div class="summary-section">
        <button class="btn-back" id="backToOrder"><i class="fa-solid fa-arrow-left"></i> Edit Order</button>
        <h2>Purchase Order Summary</h2>
        <div id="paymentItems"></div>
    </div>

    <div class="action-section">
        <div class="total-box">
            <div class="total-label">Total Cost</div>
            <div class="total-amount" id="paymentTotal">RM 0.00</div>
        </div>

        <div style="color: #666; font-size: 0.9rem; text-align: center;">
            <i class="fa-solid fa-circle-info"></i> 
            By confirming, stock levels will be updated immediately.
        </div>

        <button class="btn-confirm" id="confirmBtn">
            <i class="fa-solid fa-check-circle"></i> Confirm Purchase Order
        </button>
    </div>
</div>

<div id="invoiceModal" class="modal">
    <div class="modal-content">
        <div id="invoice-print-area">
            </div>
        <div style="margin-top: 30px; text-align: right; border-top: 2px solid #eee; padding-top: 20px;">
            <button id="printInvoiceBtn" style="padding: 12px 25px; background: #34495e; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">
                <i class="fa-solid fa-print"></i> Print PO
            </button>
            <button id="finishOrderBtn" style="padding: 12px 25px; background: #27ae60; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; margin-left: 10px;">
                Done
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // 1. Retrieve Cart from LocalStorage
    let poData = JSON.parse(localStorage.getItem('userCart')) || [];
    let grandTotal = 0;

    // 2. Load Summary immediately
    const container = $('#paymentItems');
    container.empty();

    if (poData.length === 0) {
        container.html("<p>No items in draft.</p>");
        $('#confirmBtn').prop('disabled', true);
    } else {
        poData.forEach(item => {
            let sub = item.price * item.qty;
            grandTotal += sub;
            container.append(`
                <div class="summary-item">
                    <div>
                        <strong>${item.name}</strong><br>
                        <small style="color:#777">ID: ${item.id}</small>
                    </div>
                    <div style="text-align:right">
                        <div>${item.qty} Units x RM ${item.price.toFixed(2)}</div>
                        <strong style="color:#2c3e50">RM ${sub.toFixed(2)}</strong>
                    </div>
                </div>
            `);
        });
        $('#paymentTotal').text("RM " + grandTotal.toFixed(2));
    }

    // 3. Confirm & Update Database
    $('#confirmBtn').on('click', function() {
        if (!confirm("Confirm this Purchase Order? Stock will be updated.")) return;

        // Prepare Payload
        const orderData = {
            cart: poData,
            total: grandTotal,
            supplierId: poData[0].supplierId 
        };

        // Disable button to prevent double click
        $(this).prop('disabled', true).text('Processing...');

        $.ajax({
            url: '../../Controller/OrderController.php',
            type: 'POST',
            data: JSON.stringify(orderData),
            contentType: 'application/json',
            success: function(response) {
                // Ensure response is an object
                let res = (typeof response === "string") ? JSON.parse(response) : response;
                
                if (res.status === 'success') {
                    generatePOInvoice(res.orderID, orderData);
                } else {
                    alert("Error: " + res.message);
                    $('#confirmBtn').prop('disabled', false).text('Confirm Purchase Order');
                }
            },
            error: function() {
                alert("Server connection error");
                $('#confirmBtn').prop('disabled', false).text('Confirm Purchase Order');
            }
        });
    });

    // 4. Generate Formal Purchase Order HTML
    function generatePOInvoice(orderID, data) {
        const date = new Date().toLocaleString();
        const supplierName = data.cart[0].supplierName;

        let html = `
            <div style="font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; padding: 20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 40px; border-bottom: 3px solid #333; padding-bottom: 20px;">
                    <div>
                        <h1 style="margin: 0; font-size: 24px;">PURCHASE ORDER</h1>
                        <p style="margin: 5px 0;">2ENTRAL SPORTS MANAGEMENT</p>
                    </div>
                    <div style="text-align: right;">
                        <h3 style="margin: 0;">PO #: ${orderID}</h3>
                        <p style="margin: 5px 0;">Date: ${date}</p>
                        <p style="margin: 5px 0; color: green; font-weight:bold;">STATUS: PENDING</p>
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <strong>VENDOR:</strong><br>
                    <span style="font-size: 1.2rem;">${supplierName}</span>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f4f4f4; border-bottom: 2px solid #000;">
                            <th style="padding: 10px; text-align: left;">Product ID</th>
                            <th style="padding: 10px; text-align: left;">Description</th>
                            <th style="padding: 10px; text-align: center;">Qty</th>
                            <th style="padding: 10px; text-align: right;">Unit Price</th>
                            <th style="padding: 10px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.cart.forEach(item => {
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;">${item.id}</td>
                    <td style="padding: 10px;">${item.name}</td>
                    <td style="padding: 10px; text-align: center;">${item.qty}</td>
                    <td style="padding: 10px; text-align: right;">${item.price.toFixed(2)}</td>
                    <td style="padding: 10px; text-align: right;">${(item.price * item.qty).toFixed(2)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 20px;">
                    <h2 style="margin: 0;">Grand Total: RM ${data.total.toFixed(2)}</h2>
                </div>

                <div style="margin-top: 80px; display: flex; justify-content: space-between;">
                    <div style="text-align: center; border-top: 1px solid #333; width: 200px; padding-top: 10px;">
                        Authorized Signature
                    </div>
                    <div style="text-align: center; border-top: 1px solid #333; width: 200px; padding-top: 10px;">
                        Date
                    </div>
                </div>
            </div>
        `;

        $('#invoice-print-area').html(html);
        $('#invoiceModal').show();
    }

    // 5. Controls
    $('#printInvoiceBtn').on('click', function() { window.print(); });
    
    $('#finishOrderBtn').on('click', function() {
        localStorage.removeItem('userCart'); 
        $('#ajax-result').load('Order.php');
    });

    $('#backToOrder').on('click', function() {
        $('#ajax-result').load('Order.php');
    });

})();
</script>
</div>