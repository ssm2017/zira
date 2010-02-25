/* 
 * Copyright (c) Intel Corporation
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * -- Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * -- Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * -- Neither the name of the Intel Corporation nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE INTEL OR ITS
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

using System;
using System.Collections.Generic;
using System.Reflection;
using log4net;
using Mono.Addins;
using Nini.Config;
using OpenMetaverse;
using OpenMetaverse.StructuredData;
using OpenSim.Framework;
using OpenSim.Framework.Client;
using OpenSim.Region.Framework.Interfaces;
using OpenSim.Region.Framework.Scenes;

namespace SimianGrid
{
    [Extension(Path = "/OpenSim/RegionModules", NodeName = "RegionModule")]
    public class SimianProfiles : INonSharedRegionModule
    {
        private static readonly ILog m_log =
                LogManager.GetLogger(
                MethodBase.GetCurrentMethod().DeclaringType);

        private string m_serverUrl = String.Empty;

        #region INonSharedRegionModule

        public Type ReplaceableInterface { get { return null; } }
        public void RegionLoaded(Scene scene) { }
        public void Close() { }

        public SimianProfiles() { }
        public string Name { get { return "SimianProfiles"; } }
        public void AddRegion(Scene scene) { scene.EventManager.OnClientConnect += ClientConnectHandler; }
        public void RemoveRegion(Scene scene) { scene.EventManager.OnClientConnect -= ClientConnectHandler; }

        #endregion INonSharedRegionModule

        public SimianProfiles(IConfigSource source)
        {
            Initialise(source);
        }

        public void Initialise(IConfigSource source)
        {
            IConfig gridConfig = source.Configs["UserAccountService"];
            if (gridConfig == null)
            {
                m_log.Error("[PROFILES]: UserAccountService missing from OpenSim.ini");
                throw new Exception("Profiles init error");
            }

            string serviceUrl = gridConfig.GetString("UserAccountServerURI");
            if (String.IsNullOrEmpty(serviceUrl))
            {
                m_log.Error("[PROFILES]: No UserAccountServerURI in section UserAccountService");
                throw new Exception("Profiles init error");
            }

            if (!serviceUrl.EndsWith("/"))
                serviceUrl = serviceUrl + '/';

            m_serverUrl = serviceUrl;
        }

        private void ClientConnectHandler(IClientCore clientCore)
        {
            if (clientCore is IClientAPI)
            {
                IClientAPI client = (IClientAPI)clientCore;

                // Classifieds
                //client.AddGenericPacketHandler("avatarclassifiedsrequest", AvatarClassifiedsRequestHandler);
                client.OnClassifiedInfoRequest += ClassifiedInfoRequestHandler;
                client.OnClassifiedInfoUpdate += ClassifiedInfoUpdateHandler;
                client.OnClassifiedDelete += ClassifiedDeleteHandler;

                // Picks
                client.AddGenericPacketHandler("avatarpicksrequest", HandleAvatarPicksRequest);
                client.AddGenericPacketHandler("pickinforequest", HandlePickInfoRequest);
                client.OnPickInfoUpdate += PickInfoUpdateHandler;
                client.OnPickDelete += PickDeleteHandler;

                // Notes
                client.AddGenericPacketHandler("avatarnotesrequest", HandleAvatarNotesRequest);
                client.OnAvatarNotesUpdate += AvatarNotesUpdateHandler;

                // Profiles
                client.OnRequestAvatarProperties += RequestAvatarPropertiesHandler;
                client.OnUpdateAvatarProperties += UpdateAvatarPropertiesHandler;
                client.OnAvatarInterestUpdate += AvatarInterestUpdateHandler;
                client.OnUserInfoRequest += UserInfoRequestHandler;
                client.OnUpdateUserInfo += UpdateUserInfoHandler;
            }
        }

        #region Classifieds

        private void ClassifiedInfoRequestHandler(UUID classifiedID, IClientAPI client)
        {
        }

        private void ClassifiedInfoUpdateHandler(UUID classifiedID, uint category, string name, string description,
            UUID parcelID, uint parentEstate, UUID snapshotID, Vector3 globalPos, byte classifiedFlags, int price,
            IClientAPI client)
        {
        }

        private void ClassifiedDeleteHandler(UUID classifiedID, IClientAPI client)
        {
        }

        #endregion Classifieds

        #region Picks

        private void HandleAvatarPicksRequest(Object sender, string method, List<String> args)
        {
        }

        private void HandlePickInfoRequest(Object sender, string method, List<String> args)
        {
        }

        private void PickInfoUpdateHandler(IClientAPI client, UUID pickID, UUID creatorID, bool topPick, string name,
            string desc, UUID snapshotID, int sortOrder, bool enabled)
        {
        }

        private void PickDeleteHandler(IClientAPI client, UUID pickID)
        {
        }

        #endregion Picks

        #region Notes

        private void HandleAvatarNotesRequest(Object sender, string method, List<String> args)
        {
        }

        private void AvatarNotesUpdateHandler(IClientAPI client, UUID targetID, string notes)
        {
        }

        #endregion Notes

        #region Profiles

        private void RequestAvatarPropertiesHandler(IClientAPI client, UUID avatarID)
        {
            //client.SendAvatarProperties(avatarID, aboutText, bornOn, charterMember, flAbout, flags, flImageID, imageID, profileUrl, partnerID);
        }

        private void UpdateAvatarPropertiesHandler(IClientAPI client, UserProfileData profileData)
        {
            //UserProfile.ID = AgentId;
            //UserProfile.AboutText = Utils.BytesToString(Properties.AboutText);
            //UserProfile.FirstLifeAboutText = Utils.BytesToString(Properties.FLAboutText);
            //UserProfile.FirstLifeImage = Properties.FLImageID;
            //UserProfile.Image = Properties.ImageID;
            //UserProfile.ProfileUrl = Utils.BytesToString(Properties.ProfileURL);
            //UserProfile.UserFlags &= ~3;
        }

        private void AvatarInterestUpdateHandler(IClientAPI client, uint wantmask, string wanttext, uint skillsmask,
            string skillstext, string languages)
        {
            m_log.Error("[PROFILES]: AvatarInterestUpdateHandler");
        }

        private void UserInfoRequestHandler(IClientAPI client)
        {
            m_log.Error("[PROFILES]: UserInfoRequestHandler");

            //client.SendUserInfoReply(imViaEmail, visible, email);
        }

        private void UpdateUserInfoHandler(bool imViaEmail, bool visible, IClientAPI client)
        {
            m_log.Error("[PROFILES]: UpdateUserInfoHandler");
        }

        #endregion Profiles
    }
}
