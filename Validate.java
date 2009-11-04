import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.qtitools.qti.node.content.ItemBody;
import org.qtitools.qti.node.content.basic.TextRun;
import org.qtitools.qti.node.content.xhtml.text.Div;
import org.qtitools.qti.node.content.xhtml.text.P;
import org.qtitools.qti.node.expression.general.BaseValue;
import org.qtitools.qti.node.expression.general.Variable;
import org.qtitools.qti.node.expression.operator.Gte;
import org.qtitools.qti.node.item.AssessmentItem;
import org.qtitools.qti.node.item.interaction.SliderInteraction;
import org.qtitools.qti.node.item.response.declaration.ResponseDeclaration;
import org.qtitools.qti.node.item.response.processing.ResponseCondition;
import org.qtitools.qti.node.item.response.processing.ResponseIf;
import org.qtitools.qti.node.item.response.processing.ResponseProcessing;
import org.qtitools.qti.node.item.response.processing.SetOutcomeValue;
import org.qtitools.qti.node.outcome.declaration.OutcomeDeclaration;
import org.qtitools.qti.node.shared.FieldValue;
import org.qtitools.qti.node.shared.declaration.DefaultValue;
import org.qtitools.qti.value.BaseType;
import org.qtitools.qti.value.Cardinality;
import org.qtitools.qti.value.IntegerValue;
import org.qtitools.qti.validation.ValidationResult;

import java.io.*;

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

		System.err.println(xml);
		System.exit(0);

		// create an assessment item
		AssessmentItem assessmentItem = new AssessmentItem("my-test-item", "Jon's demonstration item", false, false);

		// debug: output the item to stderr
		System.err.println(assessmentItem.toXmlString());

		// validate
		ValidationResult validationResult = assessmentItem.validate();

		// output all errors and warnings
		System.out.println(validationResult.getAllItems());

		// if there are errors exit with error code
		if (validationResult.getErrors().size() > 0) {
			System.exit(1);
		}

		System.exit(0);
	}

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

