<form method="POST" action="{{ route('applications.update', $application->id) }}">
    @csrf
    @method('PUT')

    <div>
        <label for="price_option">Select Price Option:</label>
        <select id="price_option" name="price_option" onchange="togglePriceInput()">
            <option value="unit" {{ $application->set_price ? '' : 'selected' }}>Unit Price</option>
            <option value="custom" {{ $application->set_price ? 'selected' : '' }}>Custom Price</option>
        </select>
    </div>

    <div id="custom_price_input" style="display: {{ $application->set_price ? 'block' : 'none' }};">
        <label for="set_price">Custom Price:</label>
        <input type="number" id="set_price" name="set_price" value="{{ $application->set_price }}" step="0.01">
    </div>

    <button type="submit">Save</button>
</form>

<script>
    function togglePriceInput() {
        var priceOption = document.getElementById('price_option').value;
        var customPriceInput = document.getElementById('custom_price_input');
        customPriceInput.style.display = (priceOption === 'custom') ? 'block' : 'none';
    }
</script>
