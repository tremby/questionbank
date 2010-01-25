package EPrints::Plugin::Screen::EPrint::Box::QTIBox;

use EPrints::Plugin::Screen::EPrint::Box;
@ISA = ('EPrints::Plugin::Screen::EPrint::Box');

use strict;

use Archive::Zip;

sub new {
	my ($class, %params) = @_;

	my $self = $class->SUPER::new(%params);

	# Register sub-classes but not this actual class.
	if ($class ne "EPrints::Plugin::Screen::EPrint::Box") {
		$self->{appears} = [
			{
				place		=>	"summary_bottom",
				position	=>	1000,
			},
		];
	}

	return $self;
}

sub render {
	my ($self) = @_;

	my $session = $self->{session};

	my $eprint = $self->{processor}->{eprint};
	my @documents = $eprint->get_all_documents();

	if (!scalar @documents) {
		return 0;
	}
	my $document = $documents[0];

	my $div = $session->make_element("div", "id" => "qtibox_document_" . $document->get_id());
	my $button = $session->make_element("input",
		"class"		=>	"ep_form_action_button",
		"type"		=>	"button",
		"onclick"	=>	"qtibox_playitem(" . $document->get_id() . ")",
		"value"		=>	"Play QTI item",
	);
	$div->appendChild($button);

	return $div;
}

sub isqti {
	my ($self, $xml) = @_;
	my $doc = eval { EPrints::XML::parse_xml_string($xml); };
	if ($@) {
		my $err = $@;
		$err =~ s# at /.*##;
		#print STDERR "error parsing XML\n";
		return 0;
	}
	#print STDERR "document root element is " . $doc->getDocumentElement()->getTagName() . "\n";
	if ($doc->getDocumentElement()->getTagName() eq "assessmentItem") {
		#print STDERR "accept assessment item\n";
		return 1;
	}
	if ($doc->getDocumentElement()->getTagName() eq "assessmentTest") {
		#print STDERR "accept assessment test\n";
		return 1;
	}
	#print STDERR "doesn't look like QTI\n";
	return 0;
}

sub can_be_viewed {
	my ($self) = @_;
	my $eprint = $self->{processor}->{eprint};
	my @documents = $eprint->get_all_documents();

	if (!scalar @documents) {
		return 0;
	}
	my $document = $documents[0];

	#print STDERR "document filename is '" . $document->get_value("main") . "'\n";
	if ($document->get_value("main") =~ /.zip$/i) {
		#print STDERR "looking at contents\n";
		my $foundqti = 0;
		my $zip = Archive::Zip->new($document->local_path() . "/" . $document->get_value("main"));
		my $manifests = $zip->membersMatching('imsmanifest.xml');
		if ($manifests == 0) {
			#print STDERR "not a content package: no imsmanifest.xml file\n";
			return 0;
		}
		#print STDERR "found manifest file\n";
		my @xmlfiles = $zip->membersMatching('.*\.xml');
		foreach (@xmlfiles) {
			if ($_->fileName() eq "imsmanifest.xml") {
				#print STDERR "skipping manifest file\n";
				next;
			}
			#print STDERR "testing xml file " . $_->fileName() . "\n";
			my $xml = $zip->contents($_);
			if ($self->isqti($xml)) {
				$foundqti = 1;
				last;
			}
		}
		if (!$foundqti) {
			#print STDERR "not a qti content package: no qti found\n";
			return 0;
		}
	} elsif ($document->get_value("main") =~ /.xml$/i) {
		open(FILE, $document->local_path() . "/" . $document->get_value("main")) or die "Couldn't open file: $!";
		my $xml = join("", <FILE>);
		close FILE;
		if (!$self->isqti($xml)) {
			#print STDERR "not a qti file\n";
			return 0;
		}
	} else {
		return 0;
	}

	return 1;
}

1;
