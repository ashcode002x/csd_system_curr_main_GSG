$(document).ready(function () {
    // Handle Update button click
    $('.btn-update').on('click', function () {
        var itemId = $(this).data('item-id');
        var quantity = $(this).data('quantity');
        var stockQuantity = $(this).data('stock-quantity');

        $('#updateItemId').val(itemId);
        $('#quantity').val(quantity);
        $('#updateStockQuantity').val(stockQuantity);

        $('#updateModal').modal('show');
    });

    // Handle form submission for updating quantity
    $('#updateForm').on('submit', function (e) {
        e.preventDefault();
        var itemId = $('#updateItemId').val();
        var quantity = $('#quantity').val();

        $.ajax({
            url: 'update_order.php',
            type: 'POST',
            data: { item_id: itemId, quantity: quantity },
            success: function (response) {
                $('#updateModal').modal('hide');
                alert('Quantity updated successfully.');
                location.reload(); // Reload the page to see the changes
            },
            error: function () {
                alert('An error occurred while updating the quantity.');
            }
        });
    });
});
