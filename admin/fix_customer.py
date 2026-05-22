from pathlib import Path
path = Path('customer.php')
data = path.read_text()
mid = len(data)//2
if data[:mid] == data[mid:]:
    data = data[:mid]
replacements = {
    "dirname(__DIR__) + '/images/customer_logo'": "dirname(__DIR__) . '/images/customer_logo'",
    "'/' +": "'/' . ",
    "/' + $fileName": "' . $fileName"
}
for old, new in replacements.items():
    data = data.replace(old, new)
path.write_text(data)
