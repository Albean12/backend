import sys
import easyocr
import json
import re
import numpy as np

# Get the image path and expected ID type
image_path = sys.argv[1]
expected_id_type = sys.argv[2].lower()

# Initialize EasyOCR with local model storage
reader = easyocr.Reader(
    ['en', 'tl'], 
    model_storage_directory="C:\\Users\\shana\\Documents\\dorm-vision\\backend\\.venv\\Lib\\site-packages\\easyocr\\model"
)

# Perform OCR on the uploaded ID
result = reader.readtext(image_path)

# Convert NumPy data types to standard Python types
parsed_results = []
for entry in result:
    box = [[int(coord) for coord in point] for point in entry[0]]  # Convert np.int32 to int
    text = entry[1]
    confidence = float(entry[2])  # Convert np.float64 to float
    parsed_results.append({"box": box, "text": text, "confidence": confidence})

# Extract text from OCR result
extracted_text = " ".join([item["text"] for item in parsed_results]).lower()

# Define expected keywords for each ID type
id_keywords = {
    "national id": ["republic of the philippines", "philippine identification", "national id"],
    "postal id": ["postal id", "philippine postal corporation", "postal identity card"],
    "umid": ["umid", "unified multi-purpose id", "gsis", "sss"],
    "passport": ["passport", "republic of the philippines", "department of foreign affairs"],
    "driver's license": ["driver's license", "land transportation office", "license no"],
    "philhealth id": ["philhealth", "philippine health insurance corporation"],
    "school id": ["school id", "student", "registrar"],
    "voter's id": ["commission on elections", "voter's id", "comelec"],
    "prc id": ["professional regulation commission", "prc id", "license no"]
}

# Fix: Ensure `expected_id_type` retrieves the correct keyword list
matching_keywords = next((keywords for key, keywords in id_keywords.items() if expected_id_type in key), [])

# Function to check for partial matches
def is_keyword_found(text, keywords):
    for keyword in keywords:
        if re.search(r"\b" + re.escape(keyword) + r"\b", text, re.IGNORECASE):
            return True
    return False

# Check if any keyword for the expected ID type is found in the extracted text
is_valid_id = is_keyword_found(extracted_text, matching_keywords)

# ✅ Only print final JSON output for Laravel
print(json.dumps({
    "text": extracted_text,
    "id_type_matched": is_valid_id
}))
