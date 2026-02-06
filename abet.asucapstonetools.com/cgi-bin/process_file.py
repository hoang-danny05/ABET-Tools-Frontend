#!/usr/bin/python3
import cgi
import cgitb
import sys

import csv_filter  # the file above

# Show a nice traceback in the browser if anything goes wrong
cgitb.enable()


def main() -> None:
    # Parse form (handles both GET and POST; we care about POST w/ file)
    form = cgi.FieldStorage()

    # If the form does not contain a "file" field at all:
    if "file" not in form:
        print("Content-Type: text/html")
        print()
        print("<h1>No 'file' field in form.</h1>")
        return

    file_item = form["file"]

    # User clicked Run without actually choosing a file
    if not getattr(file_item, "file", None) or not file_item.filename:
        print("Content-Type: text/html")
        print()
        print("<h1>No file uploaded.</h1>")
        return

    # Read uploaded file bytes
    data = file_item.file.read()

    # Decode bytes → text (handle UTF-8 + optional BOM)
    try:
        text = data.decode("utf-8-sig")
    except Exception:
        # Fallback if something is slightly weird with encoding
        text = data.decode("utf-8", errors="replace")

    # Run your CS/CSE filter
    filtered_csv = csv_filter.filter_cs_cse_csv(text)

    # Send the filtered CSV back as a downloadable file
    print("Content-Type: text/csv")
    print('Content-Disposition: attachment; filename="cs_cse_filtered.csv"')
    print()  # blank line ends headers

    # Write CSV text to the response
    sys.stdout.write(filtered_csv)


if __name__ == "__main__":
    main()