/*	Commandline QTI validator
 *	Written in 2009 November by Bart Nagel (bjn@ecs.soton.ac.uk)
 *	Proper University of Southampton copyright notice coming soon
 */

import java.io.*;
import java.util.List;

import org.qtitools.qti.node.item.AssessmentItem;
import org.qtitools.qti.validation.ValidationResult;
import org.qtitools.qti.validation.ValidationItem;
import org.qtitools.qti.exception.QTIParseException;

import org.xml.sax.SAXParseException;

public class Validate {
	public static void main(String[] args) {
		// exit if we don't have exactly one argument
		if (args.length != 1) {
			System.err.println("Expected one argument: QTI file to validate");
			System.exit(255);
		}

		String xml = "";
		try {
			xml = fileContents(args[0]);
		} catch (FileNotFoundException e) {
			System.err.println("File \"" + args[0] + "\" not found");
			System.exit(2);
		} catch (IOException e) {
			System.err.println("Error reading file \"" + args[0] + "\"");
			System.exit(3);
		}

		// load XML into an assessment item
		AssessmentItem assessmentItem = new AssessmentItem();

		try {
			assessmentItem.load(xml);
		} catch(QTIParseException e) {
			if (e.getCause() instanceof SAXParseException) {
				System.out.println(
					"Error\t"
					+ ((SAXParseException) e.getCause()).getMessage() + "\t"
					+ ((SAXParseException) e.getCause()).getLineNumber() + ":" + ((SAXParseException) e.getCause()).getColumnNumber()
				);
				System.exit(4);
			} else {
				System.out.println("Error\t" + e.toString());
				System.exit(254);
			}
		}

		// validate
		ValidationResult validationResult = assessmentItem.validate();

		// output all errors and warnings
		for (int i = 0; i < validationResult.getAllItems().size(); i++) {
			ValidationItem item = validationResult.getAllItems().get(i);
			System.out.println(
				item.getType() + "\t"
				+ item.getMessage() + "\t"
				+ item.getNode().getFullName()
			);
		}

		// if there are errors exit with error code
		if (validationResult.getErrors().size() > 0) {
			System.exit(1);
		}

		System.exit(0);
	}

	// read a file into a string
	static private String fileContents(String filename) throws FileNotFoundException, IOException {
		String lineSep = System.getProperty("line.separator");
		BufferedReader br = new BufferedReader(new FileReader(filename));
		String nextLine = "";
		StringBuffer sb = new StringBuffer();
		while ((nextLine = br.readLine()) != null) {
			sb.append(nextLine);
			sb.append(lineSep);
		}
		return sb.toString();
	}
}
