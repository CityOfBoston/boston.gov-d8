// Import not needed because React & ReactDOM are loaded by *.libraries.yml
// import React from 'react';
// import ReactDOM from 'react-dom';

// # Example 1: Simple Hello World code

class MNLItems extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      error: null,
      isLoaded: false,
      items: []
    };
  }

  componentDidMount() {
    fetch("https://jsonplaceholder.typicode.com/photos")
      .then(res => res.json())
      .then(
        (result) => {
        	//console.log(result)
          this.setState({
            isLoaded: true,
            items: result
          });
        },
        // Note: it's important to handle errors here
        // instead of a catch() block so that we don't swallow
        // exceptions from actual bugs in components.
        (error) => {
          this.setState({
            isLoaded: true,
            error
          });
        }
      )
  }
  render() {
   const ice = this.state.items;
   //const bgImg = '';
	return (
    <div className="g b--w">
          {ice.slice(0, 20).map(item => (
            <a className="cd g--4 g--4--sl m-t500 bibblio" href={item.url}>
            	<div className="cd-ic" style={{backgroundImage:'url('+item.url+')'}}></div>
              	<div className="cd-c">
		      			<div className="cd-t">{item.title}}</div>
		      			<div className="cd-d">The Digital Team kicking off the Boston.gov redesign with IDEO and Acquia.For Boston’s Digital Team, 2015 was about building a stronger foundation,…</div>
		      		</div>
            </a>
          ))}
    </div>
        );
  }
}

class MNLTemplate extends React.Component {
  render () {
    return <div className="g b--w">
		      	<a className="cd g--4 g--4--sl m-t500 bibblio" href={'https://www.boston.gov/news/stacking-team-2016'}>
		      		<div className="cd-ic" style={{backgroundImage:'url(https://boston.gov/sites/default/files/styles/grid_card_image/public/roslindale.jpg)'}}></div>
		      		<div className="cd-c">
		      			<div className="cd-t">Stacking the team for 2016</div>
		      			<div className="cd-d">The Digital Team kicking off the Boston.gov redesign with IDEO and Acquia.For Boston’s Digital Team, 2015 was about building a stronger foundation,…</div>
		      		</div>
		      	</a>
		      	<a className="cd g--4 g--4--sl m-t500 bibblio" href={'https://www.boston.gov/news/stacking-team-2016'}>
		      		<div className="cd-ic" style={{backgroundImage:'url(https://boston.gov/sites/default/files/styles/grid_card_image/public/roslindale.jpg)'}}></div>
		      		<div className="cd-c">
		      			<div className="cd-t">Stacking the team for 2016</div>
		      			<div className="cd-d">The Digital Team kicking off the Boston.gov redesign with IDEO and Acquia.For Boston’s Digital Team, 2015 was about building a stronger foundation,…</div>
		      		</div>
		      	</a>
		      	<a className="cd g--4 g--4--sl m-t500 bibblio" href={'https://www.boston.gov/news/stacking-team-2016'}>
		      	<div className="cd-ic" style={{backgroundImage:'url(https://boston.gov/sites/default/files/styles/grid_card_image/public/roslindale.jpg)'}}></div>
		      		<div className="cd-c">
		      			<div className="cd-t">Stacking the team for 2016</div>
		      			<div className="cd-d">The Digital Team kicking off the Boston.gov redesign with IDEO and Acquia.For Boston’s Digital Team, 2015 was about building a stronger foundation,…</div>
		      		</div>
		      	</a>
		    </div>
  }
}

const el = document.getElementById("react-app")
ReactDOM.render(<MNLItems/>, el)