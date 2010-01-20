package EPrints::Plugin::Screen::EPrint::Box::QTIBox;

use EPrints::Plugin::Screen::EPrint::Box;
@ISA = ('EPrints::Plugin::Screen::EPrint::Box');

use strict;

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
		return $session->make_text("");
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

1;
