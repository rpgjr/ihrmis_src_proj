// --------------------------------------------------------------------------------------
// DEVELOPER: SEAN TERRENCE A. CALZADA
// PAGE COMPONENT: LB-110
// COMPANY: DEPARTMENT OF SCIENCE AND TECHNOLOGY
// DATE: SEPTEMBER 1-2 2021
// --------------------------------------------------------------------------------------

import React, { useEffect, useState } from "react";

import { useToggleHelper } from "../../../helpers/use_hooks/toggle_helper.js";
import { useSelector } from "react-redux";
import NotificationComponent from "../../common/notification/notification_component.js";
import NextInRankMain from "../plantilla/page_component/next_in_rank_modal/next_in_rank_main";
//Main Component

const data = [
	{ label: "asdfasdfasd", link: "dhezsd" },
	{ label: "dfas", link: "" },
	{ label: "asdfasddfdsfasd", link: "" },
];

const LibraryView = ({}) => {
	//STATES/HOOK
	const [showModal, setShowModal] = useToggleHelper(false); //SHOW MODAL HOOK
	const [showPosModal, setShowPosModal] = useToggleHelper(false); //SHOW MODAL HOOK
	const [showTempModal, setShowTempModal] = useToggleHelper(false); //SHOW MODAL HOOK
	const [showNIPModal, setShowNIPModal] = useToggleHelper(false); //SHOW MODAL HOOK

	const { count } = useSelector((state) => state.vacant);

	return (
		<div style={{ margin: "20px" }}>
			<h1>Library {count}</h1>
			<button onClick={() => {}}>Click Me</button> <br />
			<NotificationComponent />
			<br />
			<div style={{ display: "flex", gap: 12 }}>
				{/* <ButtonComponent onClick={() => setShowModal()} buttonName="Email" />
        <ButtonComponent onClick={() => setShowPosModal()} buttonName="Position" />
        <ButtonComponent onClick={() => setShowTempModal()} buttonName="Template" /> */}
				{/* <ButtonComponent onClick={() => setShowNIPModal()} buttonName="Next-In-Rank" /> */}
				<NextInRankMain />
			</div>
			{/* <NextInRankModal onClose={() => setShowNIPModal()} isDisplay={showNIPModal} /> */}
			{/* <PositionModal onClose={() => setShowPosModal()} isDisplay={showPosModal} /> */}
			{/* <PlantillaEmailModal onClose={() => setShowModal()} isDisplay={showModal} plantillaId={2} /> */}
			{/* <AddEmailTemplateModal onClose={() => setShowTempModal()} isDisplay={showTempModal} /> */}
			{/* <br />
      <div style={{ float: "right" }}>
        <DropdownViewComponent className="sadfasdf" itemList={data} alignItems="end" title={<MdMoreHoriz />} />
      </div> */}
			{/* adsfasdf */}
		</div>
	);
};

export default LibraryView;
